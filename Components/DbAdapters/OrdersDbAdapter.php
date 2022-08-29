<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Shopware\Components\Model\Exception\ModelNotFoundException;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\DetailStatus;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseServiceInterface;
use SwagImportExport\Components\Utils\DbAdapterHelper;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\OrderValidator;

class OrdersDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    private const ORDERS_DB_ADAPTER_GET_QUERY = 'OrdersDbAdapter::getQuery';
    private const ORDERS_DB_ADAPTER_ADJUST_COLUMNS = 'OrdersDbAdapter::adjustColumns';

    protected ModelManager $modelManager;

    /**
     * @var array<string>
     */
    protected array $logMessages = [];

    protected ?string $logState = null;

    protected OrderValidator $validator;

    private UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService;

    private \Enlight_Event_EventManager $eventManager;

    private \Shopware_Components_Config $config;

    public function __construct(
        ModelManager $manager,
        UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Config $config
    ) {
        $this->modelManager = $manager;
        $this->underscoreToCamelCaseService = $underscoreToCamelCaseService;
        $this->eventManager = $eventManager;

        $this->validator = new OrderValidator();
        $this->config = $config;
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::ORDER_ADAPTER;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(int $start = null, int $limit = null, array $filter = null): array
    {
        $builder = $this->modelManager->createQueryBuilder();

        $builder->select('details.id')
            ->from(Detail::class, 'details')
            ->leftJoin('details.order', 'orders');

        if (isset($filter['orderstate']) && \is_numeric($filter['orderstate'])) {
            $builder->andWhere('orders.status = :orderstate');
            $builder->setParameter('orderstate', $filter['orderstate']);
        }

        if (isset($filter['paymentstate']) && \is_numeric($filter['paymentstate'])) {
            $builder->andWhere('orders.cleared = :paymentstate');
            $builder->setParameter('paymentstate', $filter['paymentstate']);
        }

        if (isset($filter['ordernumberFrom']) && \is_numeric($filter['ordernumberFrom'])) {
            $builder->andWhere('orders.number > :orderNumberFrom');
            $builder->setParameter('orderNumberFrom', $filter['ordernumberFrom']);
        }

        if (isset($filter['dateFrom']) && $filter['dateFrom']) {
            $builder->andWhere('orders.orderTime >= :dateFrom');
            $builder->setParameter('dateFrom', $filter['dateFrom']);
        }

        if (isset($filter['dateTo']) && $filter['dateTo']) {
            $dateTo = $filter['dateTo'];
            $builder->andWhere('orders.orderTime <= :dateTo');
            $builder->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
        }

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();

        $result = [];
        if ($records) {
            foreach ($records as $value) {
                $result[] = (int) $value['id'];
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/orders/no_ids', 'Can not read orders without ids.');
            throw new \Exception($message);
        }

        if (empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/orders/no_column_names', 'Can not read orders without column names.');
            throw new \Exception($message);
        }

        $orders = $this->getBuilder($columns, $ids)->getQuery()->getResult();

        $orders = DbAdapterHelper::decodeHtmlEntities($orders);

        $result['default'] = DbAdapterHelper::escapeNewLines($orders);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function write(array $records): void
    {
        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_OrdersDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/orders/no_records',
                'No order records were found.'
            );
            throw new \Exception($message);
        }

        $orderRepository = $this->modelManager->getRepository(Detail::class);
        $orderStatusRepository = $this->modelManager->getRepository(Status::class);
        $orderDetailStatusRepository = $this->modelManager->getRepository(DetailStatus::class);

        foreach ($records['default'] as $record) {
            try {
                $orderData = [];
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, OrderValidator::$mapper);

                if (isset($record['orderDetailId']) && $record['orderDetailId']) {
                    $orderDetailModel = $orderRepository->find($record['orderDetailId']);
                } else {
                    $orderDetailModel = $orderRepository->findOneBy(['number' => $record['number']]);
                }

                if (!$orderDetailModel instanceof Detail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/orders/order_detail_id_not_found', 'Order detail id %s was not found');
                    throw new AdapterException(\sprintf($message, $record['orderDetailId']));
                }

                $orderModel = $orderDetailModel->getOrder();

                if (!$orderModel instanceof Order) {
                    throw new ModelNotFoundException(
                        Order::class,
                        $record['orderDetailId'] ?? $record['number'],
                        'orderDetailId or number'
                    );
                }

                if (isset($record['paymentId']) && \is_numeric($record['paymentId'])) {
                    $paymentStatusModel = $orderStatusRepository->find($record['cleared']);

                    if (!$paymentStatusModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/payment_status_id_not_found', 'Payment status id %s was not found for order %s');
                        throw new AdapterException(\sprintf($message, $record['cleared'], $orderModel->getNumber()));
                    }

                    $orderModel->setPaymentStatus($paymentStatusModel);
                }

                if (isset($record['status']) && \is_numeric($record['status'])) {
                    $orderStatusModel = $orderStatusRepository->find($record['status']);

                    if (!$orderStatusModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/status_not_found', 'Status %s was not found for order %s');
                        throw new AdapterException(\sprintf($message, $record['status'], $orderModel->getNumber()));
                    }

                    $orderModel->setOrderStatus($orderStatusModel);
                }

                if (isset($record['trackingCode'])) {
                    $orderModel->setTrackingCode($record['trackingCode']);
                }

                if (isset($record['comment'])) {
                    $orderModel->setComment($record['comment']);
                }

                if (isset($record['customerComment'])) {
                    $orderModel->setCustomerComment($record['customerComment']);
                }

                if (isset($record['internalComment'])) {
                    $orderModel->setInternalComment($record['internalComment']);
                }

                if (isset($record['transactionId'])) {
                    $orderModel->setTransactionId($record['transactionId']);
                }

                if (isset($record['clearedDate'])) {
                    $orderModel->setClearedDate($record['clearedDate']);
                }

                if (isset($record['shipped'])) {
                    $orderDetailModel->setShipped($record['shipped']);
                }

                if (isset($record['statusId']) && \is_numeric($record['statusId'])) {
                    $detailStatusModel = $orderDetailStatusRepository->find($record['statusId']);

                    if (!$detailStatusModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/detail_status_not_found', 'Detail status with id %s was not found');
                        throw new AdapterException(\sprintf($message, $record['statusId']));
                    }

                    $orderDetailModel->setStatus($detailStatusModel);
                }

                // prepares the detail attributes
                $orderDetailData = [];
                foreach ($record as $key => $value) {
                    if ((strpos($key, 'detailAttribute') === 0) && $newKey = preg_replace('/^detailAttribute/', '', $key)) {
                        $newKey = lcfirst($newKey);
                        $orderDetailData['attribute'][$newKey] = $value;
                        unset($record[$key]);
                    }
                }

                if (!empty($orderDetailData)) {
                    $orderDetailModel->fromArray($orderDetailData);
                }

                // prepares the attributes
                foreach ($record as $key => $value) {
                    if (strpos($key, 'attribute') === 0 && $newKey = \preg_replace('/^attribute/', '', $key)) {
                        $newKey = \lcfirst($newKey);
                        $orderData['attribute'][$newKey] = $value;
                        unset($record[$key]);
                    }
                }

                if (empty($orderData)) {
                    $orderModel->fromArray($orderData);
                }

                $this->modelManager->persist($orderModel);

                unset($orderDetailModel, $orderModel, $orderData);
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
                throw $e;
            }
        }

        $this->modelManager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    /**
     * {@inheritDoc}
     */
    public function getLogState(): ?string
    {
        return $this->logState;
    }

    /**
     * {@inheritDoc}
     */
    public function getSections(): array
    {
        return [
            [
                'id' => 'default',
                'name' => 'default',
            ],
        ];
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function getDefaultColumns(): array
    {
        $columns = [
            'details.orderId as orderId',
            'details.id as orderDetailId',
            'details.articleId as articleId',
            'details.number as number',

            'orders.customerId as customerId',
            'orders.status as status',
            'orders.cleared as cleared',
            "DATE_FORMAT(orders.orderTime, '%Y-%m-%d %H:%i:%s') as orderTime",
            'orders.transactionId as transactionId',
            'orders.partnerId as partnerId',
            'orders.shopId as shopId',
            'orders.invoiceAmount as invoiceAmount',
            'orders.invoiceAmountNet as invoiceAmountNet',
            'orders.invoiceShipping as invoiceShipping',
            'orders.invoiceShippingNet as invoiceShippingNet',
            'orders.comment as comment',
            'orders.customerComment as customerComment',
            'orders.internalComment as internalComment',
            'orders.net as net',
            'orders.taxFree as taxFree',
            'orders.temporaryId as temporaryId',
            'orders.referer as referer',
            "DATE_FORMAT(orders.clearedDate, '%Y-%m-%d %H:%i:%s') as clearedDate",
            'orders.trackingCode as trackingCode',
            'orders.languageIso as languageIso',
            'orders.currency as currency',
            'orders.currencyFactor as currencyFactor',
            'orders.remoteAddress as remoteAddress',
            'orders.deviceType as deviceType',
            'payment.id as paymentId',
            'payment.description as paymentDescription',
            'paymentStatus.id as paymentStatusId',
            'dispatch.id as dispatchId',
            'dispatch.description as dispatchDescription',

            'details.taxId as taxId',
            'details.taxRate as taxRate',
            'details.statusId as statusId',
            'details.articleNumber as articleNumber',
            'details.articleName as articleName',
            'details.price as price',
            'details.quantity as quantity',
            'details.price * details.quantity as invoice',
            'details.shipped as shipped',
            'details.shippedGroup as shippedGroup',
            "DATE_FORMAT(details.releaseDate, '%Y-%m-%d') as releasedate",
            'taxes.tax as tax',
            'details.esdArticle as esd',
            'details.config as config',
            'details.mode as mode',
            'details.ean as ean',
            'details.packUnit as packUnit',
            'details.unit as unit',
        ];

        $billingColumns = [
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.salutation as billingSalutation',
            'billing.firstName as billingFirstname',
            'billing.lastName as billingLastname',
            'billing.street as billingStreet',
            'billing.zipCode as billingZipcode',
            'billing.city as billingCity',
            'billing.vatId as billingVatId',
            'billing.phone as billingPhone',
            'billingState.id as billingStateId',
            'billingState.name as billingStateName',
            'billingCountry.name as billingCountryName',
            'billingCountry.isoName as billingCountryen',
            'billingCountry.iso as billingCountryIso',
            'billing.additionalAddressLine1 as billingAdditionalAddressLine1',
            'billing.additionalAddressLine2 as billingAdditionalAddressLine2',
        ];

        $columns = \array_merge($columns, $billingColumns);

        $shippingColumns = [
            'shipping.company as shippingCompany',
            'shipping.department as shippingDepartment',
            'shipping.salutation as shippingSalutation',
            'shipping.firstName as shippingFirstname',
            'shipping.lastName as shippingLastname',
            'shipping.street as shippingStreet',
            'shipping.zipCode as shippingZipcode',
            'shipping.city as shippingCity',
            'shipping.phone as shippingPhone',
            'shippingState.id as shippingStateId',
            'shippingState.name as shippingStateName',
            'shippingCountry.name as shippingCountryName',
            'shippingCountry.isoName as shippingCountryIsoName',
            'shippingCountry.iso as shippingCountryIso',
            'shipping.additionalAddressLine1 as shippingAdditionalAddressLine1',
            'shipping.additionalAddressLine2 as shippingAdditionalAddressLine2',
        ];

        $columns = \array_merge($columns, $shippingColumns);

        $customerColumns = [
            'customer.email as email',
            'customer.groupKey as customergroup',
            'customer.newsletter as newsletter',
            'customer.affiliate as affiliate',
            'customer.number as customerNumber',
        ];

        $columns = \array_merge($columns, $customerColumns);

        $attributesSelect = $this->getAttributes('s_order_details_attributes', 'detailAttr', 'detailAttribute', ['detailid']);
        if (!empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        $attributesSelect = $this->getAttributes('s_order_attributes', 'attr', 'attribute', ['orderid']);

        if (!empty($attributesSelect)) {
            $columns = \array_merge($columns, $attributesSelect);
        }

        return $this->eventManager->filter(self::ORDERS_DB_ADAPTER_ADJUST_COLUMNS, $columns);
    }

    private function saveMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    private function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    private function setLogState(string $logState): void
    {
        $this->logState = $logState;
    }

    /**
     * @param array<string> $ignoredFields
     *
     * @return array<string>
     */
    private function getAttributes(string $tableName, string $joinAlias, string $prefix, array $ignoredFields): array
    {
        $columns = $this->modelManager->getConnection()->getSchemaManager()->listTableColumns($tableName);
        unset($columns['id']);

        foreach ($ignoredFields as $field) {
            unset($columns[$field]);
        }

        unset($columns['id'], $columns['orderid']);

        $attributes = \array_map(function ($column) {
            return $column->getName();
        }, $columns);

        $attributesSelect = [];
        foreach ($attributes as $attribute) {
            $catAttr = $this->underscoreToCamelCaseService->underscoreToCamelCase($attribute);

            if (empty($catAttr)) {
                continue;
            }

            $attributesSelect[] = \sprintf('%s.%s as %s%s', $joinAlias, $catAttr, $prefix, \ucwords($catAttr));
        }

        return $attributesSelect;
    }

    /**
     * @param array<array<string>|string> $columns
     * @param array<int>                  $ids
     */
    private function getBuilder(array $columns, array $ids): QueryBuilder
    {
        $builder = $this->modelManager->createQueryBuilder();

        $builder->select($columns)
            ->from(Detail::class, 'details')
            ->leftJoin('details.order', 'orders')
            ->leftJoin('details.tax', 'taxes')
            ->leftJoin('orders.billing', 'billing')
            ->leftJoin('billing.country', 'billingCountry')
            ->leftJoin('billing.state', 'billingState')
            ->leftJoin('orders.shipping', 'shipping')
            ->leftJoin('shipping.country', 'shippingCountry')
            ->leftJoin('shipping.state', 'shippingState')
            ->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.paymentStatus', 'paymentStatus')
            ->leftJoin('orders.orderStatus', 'orderStatus')
            ->leftJoin('orders.dispatch', 'dispatch')
            ->leftJoin('orders.customer', 'customer')
            ->leftJoin('orders.attribute', 'attr')
            ->leftJoin('details.attribute', 'detailAttr')
            ->where('details.id IN (:ids)')
            ->setParameter('ids', $ids);

        $this->eventManager->notify(self::ORDERS_DB_ADAPTER_GET_QUERY, [
            'builder' => $builder,
        ]);

        return $builder;
    }
}
