<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class MainOrdersDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    private $manager = null;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db = null;

    /**
     * @var array
     */
    protected $unprocessedData;

    /**
     * @var array
     */
    protected $logMessages;

    /**
     * @var string
     */
    protected $logState;

    /**
     * @return ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    /**
     * @return \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    public function getDb()
    {
        if ($this->db === null) {
            $this->db = Shopware()->Db();
        }

        return $this->db;
    }

    /**
     * Returns orders' ids. Executed during `prepareExport`.
     *
     * @param int $start
     * @param int $limit
     * @param array $filter
     * @return array
     */
    public function readRecordIds($start = null, $limit = null, $filter = null)
    {
        $connection = $this->getManager()->getConnection();

        /* @var \Doctrine\DBAL\Query\QueryBuilder */
        $builder = $connection->createQueryBuilder();
        $builder->select('id')
                ->from('s_order');

        if (isset($filter['orderstate']) && is_numeric($filter['orderstate'])) {
            $builder->andWhere('status = :orderstate');
            $builder->setParameter('orderstate', $filter['orderstate']);
        }

        if (isset($filter['paymentstate']) && is_numeric($filter['paymentstate'])) {
            $builder->andWhere('cleared = :paymentstate');
            $builder->setParameter('paymentstate', $filter['paymentstate']);
        }

        if (isset($filter['ordernumberFrom']) && is_numeric($filter['ordernumberFrom'])) {
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

        return is_array($ids) ? $ids : array();
    }

    /**
     * Reads order data from `s_order` table
     *
     * @param $ids
     * @param $columns
     * @return array
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/orders/no_ids',
                'Can not read orders without ids.'
            );
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/orders/no_column_names',
                'Can not read orders without column names.'
            );
            throw new \Exception($message);
        }

        $result = array();

        // orders
        $orders = array();
        if (!empty($columns['order'])) {
            $orderBuilder = $this->getOrderBuilder($columns['order'], $ids);
            $orders = $orderBuilder->getQuery()->getResult();
            $orders = DbAdapterHelper::decodeHtmlEntities($orders);
            $orders = DbAdapterHelper::escapeNewLines($orders);
            $result = array('order' => $orders);
        }

        if (!empty($columns['taxRateSum'])) {
            $taxRateSums = $this->getTaxSums($ids, $orders);
            $result['taxRateSum'] = $taxRateSums;
        }

        return $result;
    }

    /**
     * Returns default columns
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        $columns['order'] = $this->getOrderColumns();
        $columns['taxRateSum'] = array('taxRateSums', 'taxRate');

        return $columns;
    }

    /**
     * @return array
     */
    public function getTaxRateSumColumns()
    {
        return array('taxRateSums', 'taxRate');
    }

    /**
     * @return array
     */
    public function getOrderColumns()
    {
        $columns = array(
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
            'paymentStatus.description as paymentState',
            'orderStatus.description as orderState',
            'orders.currency',
            'orders.currencyFactor',
            'orders.transactionId',
            'orders.trackingCode',
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
            'sCountry.name as shippingCountry'
        );

        $attributesSelect = $this->getAttributes();
        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @return array|string
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributes()
    {
        // Attributes
        $stmt = $this->getDb()->query('SELECT * FROM s_order_attributes LIMIT 1');
        $attributes = $stmt->fetch();

        if (!$attributes) {
            return '';
        }

        unset($attributes['id']);
        unset($attributes['orderID']);
        $attributes = array_keys($attributes);

        $prefix = 'attr';
        $attributesSelect = array();
        foreach ($attributes as $attribute) {
            //underscore to camel case
            //exmaple: underscore_to_camel_case -> underscoreToCamelCase
            $catAttr = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute))));

            $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $catAttr, ucwords($catAttr));
        }

        return $attributesSelect;
    }

    /**
     * @param $records
     * @throws \Exception
     */
    public function write($records)
    {
        $message = SnippetsHelper::getNamespace()
            ->get('adapters/mainOrders/use_order_profile_for_import', 'For import, please use `Orders` profile!');
        throw new \Exception($message);
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');
        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * @param array $logMessages
     */
    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
    }

    /**
     * @return string
     */
    public function getLogState()
    {
        return $this->logState;
    }

    /**
     * @param $logState
     */
    public function setLogState($logState)
    {
        $this->logState = $logState;
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'order', 'name' => 'order'),
            array('id' => 'taxRateSum', 'name' => 'taxRateSum')
        );
    }

    /**
     * @return array
     */
    public function getParentKeys()
    {
        return array('orders.id as orderId');
    }

    /**
     * @param $section
     * @return bool|array
     */
    public function getColumns($section)
    {
        $method = 'get' . ucfirst($section) . 'Columns';
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    /**
     * @param array $columns
     * @param array $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getOrderBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Order\Order', 'orders')
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
     * @param array $ids
     * @param array $orders
     * @return array
     */
    private function getTaxSums($ids, $orders)
    {
        $orderRecords = array();
        foreach ($orders as $order) {
            $orderRecords[$order['orderId']] = $order;
        }

        $sum = array();
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

        $result = array();
        foreach ($sum as $orderId => $taxSum) {
            foreach ($taxSum['taxRateSums'] as $taxRate => $vat) {
                $shippingTaxRate = $this->getShippingRate(
                    $orderRecords[$orderId]['invoiceShipping'],
                    $orderRecords[$orderId]['invoiceShippingNet']
                );
                if ($taxRate == $shippingTaxRate) {
                    $vat += $orderRecords[$orderId]['taxSumShipping'];
                }

                $result[] = array(
                    'orderId' => $orderId,
                    'taxRateSums' => $vat,
                    'taxRate' => $taxRate
                );
            }
        }

        return $result;
    }

    /**
     * Get shipping tax rate
     *
     * @param float $amount
     * @param float $amountNet
     * @return float
     */
    private function getShippingRate($amount, $amountNet)
    {
        $percent = abs((1 - $amount / $amountNet) * 100);

        return round($percent);
    }

    /**
     * @param array $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getTaxSumBuilder($ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select(array('details.orderId, orders.invoiceAmount, orders.invoiceAmountNet, orders.invoiceShipping, orders.invoiceShippingNet, orders.net, details.price, details.quantity, details.taxId, details.taxRate'))
            ->from('Shopware\Models\Order\Order', 'orders')
            ->leftJoin('orders.details', 'details')
            ->where('details.orderId IN (:ids)')
            ->andWhere('orders.taxFree = 0')
            ->setParameter('ids', $ids)
            ->orderBy('details.orderId, details.taxRate', 'ASC');

        return $builder;
    }

    /**
     * @param array $taxData
     * @return float
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function calculateTaxSum($taxData)
    {
        if (empty($taxData['taxRate'])) {
            $taxModel = $this->getManager()->find('Shopware\Models\Tax\Tax', $taxData['taxId']);
            if ($taxModel && $taxModel->getId() !== 0 && $taxModel->getId() !== null && $taxModel->getTax() !== null) {
                $taxValue = $taxModel->getTax();
            }
        } else {
            $taxValue = $taxData['taxRate'];
        }

        $price = $taxData['price'] * $taxData['quantity'];
        if ($taxData['net']) {
            return round(($taxValue / 100) * $price, 2);
        } else {
            return round($price * ($taxValue / (100 + $taxValue)), 2);
        }
    }
}
