<?php

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
            $builder->andWhere('ordertime >= :dateFrom');
            $builder->setParameter('dateFrom', $filter['dateFrom']);
        }

        if (isset($filter['dateTo']) && $filter['dateTo']) {
            $dateTo = $filter['dateTo'];
            $builder->andWhere('ordertime <= :dateTo');
            $builder->setParameter('dateTo', $dateTo->get('yyyy-MM-dd HH:mm:ss'));
        }

        $builder->setFirstResult($start)
            ->setMaxResults($limit);

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

        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Order\Order', 'orders')
            ->leftJoin('orders.attribute', 'attr')
            ->leftJoin('orders.payment', 'payment')
            ->leftJoin('orders.customer', 'customer')
            ->leftJoin('orders.billing', 'billing')
            ->leftJoin('orders.shipping', 'shipping')
            ->leftJoin('shipping.country', 'shippingCountry')
            ->leftJoin('customer.group', 'customerGroup')
            ->leftJoin('orders.paymentStatus', 'paymentStatus')
            ->leftJoin('orders.orderStatus', 'orderStatus')
            ->where('orders.id IN (:ids)')
            ->setParameter('ids', $ids);

        $orders = $builder->getQuery()->getResult();
        $orders = DbAdapterHelper::decodeHtmlEntities($orders);
        $orders = DbAdapterHelper::escapeNewLines($orders);

        return array('default' => $orders);
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        $columns = array(
            'orders.id as orderId',
            'orders.number as orderNumber',
            'orders.invoiceAmount',
            'orders.invoiceAmountNet',
            'orders.invoiceShipping',
            'orders.invoiceShippingNet',
            'orders.taxFree',
            'orders.currency',
            'orders.currencyFactor',
            'payment.description as paymentName',
            'orders.transactionId',
            'orders.trackingCode',
            "DATE_FORMAT(orders.orderTime, '%Y-%m-%d %H:%i:%s') as orderTime",
            'customer.email',
            'billing.number as customerNumber',
            'billing.firstName as bFirstName',
            'billing.lastName as bLastName',
            'shippingCountry.name as countryName',
            'customerGroup.name as customerGroupName',
            'paymentStatus.description as paymentState',
            'orderStatus.description as orderState'
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
            $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

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
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'default', 'name' => 'default')
        );
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
}
