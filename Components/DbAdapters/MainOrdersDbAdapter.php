<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\StateTranslatorService;
use Shopware\Components\StateTranslatorServiceInterface;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Tax\Tax;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseServiceInterface;
use SwagImportExport\Components\Utils\DbAdapterHelper;
use SwagImportExport\Components\Utils\SnippetsHelper;

class MainOrdersDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    protected array $unprocessedData;

    protected array $logMessages = [];

    protected ?string $logState = null;

    private ModelManager $modelManager;

    private Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    private UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService;

    private StateTranslatorServiceInterface $stateTranslator;

    private \Shopware_Components_Config $config;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ModelManager $entityManager,
        UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService,
        StateTranslatorServiceInterface $stateTranslator,
        \Shopware_Components_Config $config
    ) {
        $this->db = $db;
        $this->modelManager = $entityManager;
        $this->underscoreToCamelCaseService = $underscoreToCamelCaseService;
        $this->stateTranslator = $stateTranslator;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(int $start = null, int $limit = null, array $filter = null): array
    {
        $connection = $this->modelManager->getConnection();

        /* @var \Doctrine\DBAL\Query\QueryBuilder */
        $builder = $connection->createQueryBuilder();
        $builder->select('id')
            ->from('s_order');

        if (isset($filter['orderstate']) && \is_numeric($filter['orderstate'])) {
            $builder->andWhere('status = :orderstate');
            $builder->setParameter('orderstate', $filter['orderstate']);
        }

        if (isset($filter['paymentstate']) && \is_numeric($filter['paymentstate'])) {
            $builder->andWhere('cleared = :paymentstate');
            $builder->setParameter('paymentstate', $filter['paymentstate']);
        }

        if (isset($filter['ordernumberFrom']) && \is_numeric($filter['ordernumberFrom'])) {
            $builder->andWhere('ordernumber > :orderNumberFrom');
            $builder->setParameter('orderNumberFrom', $filter['ordernumberFrom']);
        }

        if (isset($filter['dateFrom']) && $filter['dateFrom']) {
            $dateFrom = $filter['dateFrom'];
            $dateFrom->setTime(0, 0, 0);
            $builder->andWhere('ordertime >= :dateFrom');
            $builder->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'));
        }

        if (isset($filter['dateTo']) && $filter['dateTo']) {
            $dateTo = $filter['dateTo'];
            $builder->andWhere('ordertime <= :dateTo');
            $builder->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
        }

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $ids = $builder->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return \is_array($ids) ? $ids : [];
    }

    /**
     * Reads order data from `s_order` table
     *
     * @throws \RuntimeException
     */
    public function read(array $ids, array $columns): array
    {
        if (!$ids) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/orders/no_ids',
                'Can not read orders without ids.'
            );
            throw new \RuntimeException($message);
        }

        if (!$columns) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/orders/no_column_names',
                'Can not read orders without column names.'
            );
            throw new \RuntimeException($message);
        }

        $result = [];

        // orders
        $orders = [];
        if (!empty($columns['order'])) {
            $orderBuilder = $this->getOrderBuilder($columns['order'], $ids);
            $orders = $orderBuilder->getQuery()->getResult();
            $orders = DbAdapterHelper::decodeHtmlEntities($orders);
            $orders = DbAdapterHelper::escapeNewLines($orders);
            $orders = $this->addOrderAndPaymentState($orders);
            $result = ['order' => $orders];
        }

        if (!empty($columns['taxRateSum'])) {
            $taxRateSums = $this->getTaxSums($ids, $orders);
            $taxRateSums = DbAdapterHelper::decodeHtmlEntities($taxRateSums);
            $taxRateSums = DbAdapterHelper::escapeNewLines($taxRateSums);
            $result['taxRateSum'] = $taxRateSums;
        }

        return $result;
    }

    /**
     * Returns default columns
     */
    public function getDefaultColumns(): array
    {
        $columns['order'] = $this->getOrderColumns();
        $columns['taxRateSum'] = ['taxRateSums', 'taxRate'];

        return $columns;
    }

    /**
     * @return array<string>
     */
    public function getTaxRateSumColumns(): array
    {
        return ['taxRateSums', 'taxRate'];
    }

    /**
     * @return array<string>
     */
    public function getOrderColumns(): array
    {
        $columns = [
            'orders.id as orderId',
            'orders.number as orderNumber',
            'documents.documentId as invoiceNumber',
            'orders.invoiceAmount',
            'orders.invoiceAmountNet',
            'orders.invoiceShipping',
            'orders.invoiceShippingNet',
            'SUM(orders.invoiceShipping - orders.invoiceShippingNet) as taxSumShipping',
            'orders.net',
            'orders.taxFree',
            'payment.description as paymentName',
            'orders.currency',
            'orders.currencyFactor',
            'orders.transactionId',
            'orders.trackingCode',
            "DATE_FORMAT(orders.clearedDate, '%Y-%m-%d %H:%i:%s') as clearedDate",
            "DATE_FORMAT(orders.orderTime, '%Y-%m-%d %H:%i:%s') as orderTime",
            'customer.email',
            'billing.number as customerNumber',
            'customerGroup.name as customerGroupName',
            'billing.salutation as billingSalutation',
            'billing.firstName as billingFirstName',
            'billing.lastName as billingLastName',
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.street as billingStreet',
            'billing.zipCode as billingZipCode',
            'billing.city as billingCity',
            'billing.phone as billingPhone',
            'billing.additionalAddressLine1 as billingAdditionalAddressLine1',
            'billing.additionalAddressLine2 as billingAdditionalAddressLine2',
            'bState.name as billingState',
            'bCountry.name as billingCountry',
            'shipping.salutation as shippingSalutation',
            'shipping.firstName as shippingFirstName',
            'shipping.lastName as shippingLastName',
            'shipping.company as shippingCompany',
            'shipping.department as shippingDepartment',
            'shipping.street as shippingStreet',
            'shipping.zipCode as shippingZipCode',
            'shipping.city as shippingCity',
            'shipping.additionalAddressLine1 as shippingAdditionalAddressLine1',
            'shipping.additionalAddressLine2 as shippingAdditionalAddressLine2',
            'sState.name as shippingState',
            'sCountry.name as shippingCountry',
            'paymentStatus.id as paymentStateId',
            'orderStatus.id as orderStateId',
        ];

        $attributesSelect = $this->getAttributes();
        if (!empty($attributesSelect)) {
            $columns = \array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @return array<string>
     */
    public function getAttributes(): array
    {
        // Attributes
        $stmt = $this->db->query('SELECT * FROM s_order_attributes LIMIT 1');
        $attributes = $stmt->fetch();

        if (!$attributes) {
            return [];
        }

        unset($attributes['id'], $attributes['orderID']);
        $attributes = \array_keys($attributes);

        $prefix = 'attr';
        $attributesSelect = [];
        foreach ($attributes as $attribute) {
            $catAttr = $this->underscoreToCamelCaseService->underscoreToCamelCase((string) $attribute);

            if (empty($catAttr)) {
                continue;
            }

            $attributesSelect[] = \sprintf('%s.%s as attribute%s', $prefix, $catAttr, \ucwords($catAttr));
        }

        return $attributesSelect;
    }

    /**
     * @param array<string, mixed> $records
     */
    public function write(array $records): void
    {
        $message = SnippetsHelper::getNamespace()
            ->get('adapters/mainOrders/use_order_profile_for_import', 'This is only an export profile. Please use `Orders` profile for imports!');
        throw new \RuntimeException($message);
    }

    /**
     * @return array<mixed>
     */
    public function getUnprocessedData(): array
    {
        return $this->unprocessedData;
    }

    public function saveMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');
        if ($errorMode === false) {
            throw new \RuntimeException($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array<string>
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    public function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    public function getLogState(): ?string
    {
        return $this->logState;
    }

    public function setLogState(string $logState): void
    {
        $this->logState = $logState;
    }

    public function getSections(): array
    {
        return [
            ['id' => 'order', 'name' => 'order'],
            ['id' => 'taxRateSum', 'name' => 'taxRateSum'],
        ];
    }

    /**
     * @return array<string>
     */
    public function getParentKeys(): array
    {
        return ['orders.id as orderId'];
    }

    /**
     * @return array<string>
     */
    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';
        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getOrderBuilder(array $columns, array $ids): QueryBuilder
    {
        $builder = $this->modelManager->createQueryBuilder();
        $builder->select($columns)
            ->from(Order::class, 'orders')
            ->leftJoin('orders.attribute', 'attr')
            ->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.customer', 'customer')
            ->leftJoin('orders.billing', 'billing')
            ->leftJoin('orders.documents', 'documents', 'WITH', 'documents.typeId = 1')
            ->leftJoin('billing.state', 'bState')
            ->leftJoin('billing.country', 'bCountry')
            ->leftJoin('orders.shipping', 'shipping')
            ->leftJoin('shipping.state', 'sState')
            ->leftJoin('shipping.country', 'sCountry')
            ->leftJoin('customer.group', 'customerGroup')
            ->leftJoin('orders.paymentStatus', 'paymentStatus')
            ->leftJoin('orders.orderStatus', 'orderStatus')
            ->where('orders.id IN (:ids)')
            ->groupBy('orders.id')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @param array<int> $ids
     */
    public function getTaxSumBuilder(array $ids): QueryBuilder
    {
        $builder = $this->modelManager->createQueryBuilder();
        $builder->select(['details.orderId, orders.invoiceAmount, orders.invoiceAmountNet, orders.invoiceShipping, orders.invoiceShippingNet, orders.net, details.price, details.quantity, details.taxId, details.taxRate'])
            ->from(Order::class, 'orders')
            ->leftJoin('orders.details', 'details')
            ->where('details.orderId IN (:ids)')
            ->andWhere('orders.taxFree = 0')
            ->setParameter('ids', $ids)
            ->orderBy('details.orderId, details.taxRate', 'ASC');

        return $builder;
    }

    /**
     * @param array<string, mixed> $orders
     *
     * @return array<string, string>
     */
    private function addOrderAndPaymentState(array $orders): array
    {
        $states = $this->getStates();

        foreach ($orders as &$order) {
            $order['paymentState'] = $this->getStateName((int) $order['paymentStateId'], $states);
            $order['orderState'] = $this->getStateName((int) $order['orderStateId'], $states);
        }

        return $orders;
    }

    /**
     * @param array<int, string> $states
     */
    private function getStateName(int $id, array $states): string
    {
        foreach ($states as $state) {
            if ($id === (int) $state['id']) {
                return $state['description'];
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function getStates(): array
    {
        $data = $this->modelManager->getRepository(Status::class)->createQueryBuilder('state')
            ->select('state')
            ->getQuery()
            ->getArrayResult();

        foreach ($data as $key => $state) {
            $data[$key] = $this->stateTranslator->translateState(
                $state['group'] === 'state' ? StateTranslatorService::STATE_ORDER : $state['group'],
                $state
            );
        }

        return $data;
    }

    /**
     * @param array<int>           $ids
     * @param array<string, mixed> $orders
     *
     * @return array<array{orderId: int, taxRateSums: float, taxRate: string}>
     */
    private function getTaxSums(array $ids, array $orders): array
    {
        $orderRecords = [];
        foreach ($orders as $order) {
            $orderRecords[$order['orderId']] = $order;
        }

        $sum = [];
        $taxSumBuilder = $this->getTaxSumBuilder($ids);
        $taxData = $taxSumBuilder->getQuery()->getResult();
        foreach ($ids as $orderId) {
            foreach ($taxData as $data) {
                if ($data['orderId'] != $orderId) {
                    continue;
                }

                $sum[$orderId]['taxRateSums'][(string) $data['taxRate']] += $this->calculateTaxSum($data);
            }
        }

        $result = [];
        foreach ($sum as $orderId => $taxSum) {
            foreach ($taxSum['taxRateSums'] as $taxRate => $vat) {
                $shippingTaxRate = $this->getShippingRate(
                    (float) $orderRecords[$orderId]['invoiceShipping'],
                    (float) $orderRecords[$orderId]['invoiceShippingNet']
                );
                if ($taxRate == $shippingTaxRate) {
                    $vat += $orderRecords[$orderId]['taxSumShipping'];
                }

                $result[] = [
                    'orderId' => $orderId,
                    'taxRateSums' => $vat,
                    'taxRate' => $taxRate,
                ];
            }
        }

        return $result;
    }

    /**
     * Get shipping tax rate
     */
    private function getShippingRate(float $amount, float $amountNet): float
    {
        if (empty($amountNet)) {
            $amountNet = 1.0;
        }

        $percent = \abs((1 - $amount / $amountNet) * 100);

        return \round($percent);
    }

    /**
     * @param array<string, mixed> $taxData
     */
    private function calculateTaxSum(array $taxData): float
    {
        $taxValue = 0;
        if (!empty($taxData['taxRate'])) {
            $taxValue = $taxData['taxRate'];
        } elseif ($taxData['taxId'] !== null) {
            /** @var Tax $taxModel */
            $taxModel = $this->modelManager->getRepository(Tax::class)->find($taxData['taxId']);
            if ($taxModel !== null) {
                $taxId = $taxModel->getId();
                $tax = $taxModel->getTax();
                if ($taxId !== 0 && $taxId !== null && $tax !== null) {
                    $taxValue = $tax;
                }
            }
        }

        $price = $taxData['price'] * $taxData['quantity'];
        if ($taxData['net']) {
            return \round(($taxValue / 100) * $price, 2);
        }

        return \round($price * ($taxValue / (100 + $taxValue)), 2);
    }
}
