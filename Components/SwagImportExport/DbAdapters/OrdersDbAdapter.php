<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class OrdersDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * Shopware\Models\Order\Order
     */
    protected $repository;
    
    /**
     * Shopware\Models\Order\Detail
     */
    protected $detailRepository;

    /**
     * @var array
     */
    protected $unprocessedData;

    /**
     * @var array
     */
    protected $logMessages;

    /**
     * Returns record ids
     * 
     * @param int $start
     * @param int $limit
     * @param type $filter
     * @return array
     */
    public function readRecordIds($start = null, $limit = null, $filter = null)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        
        $builder->select('details.id')
                ->from('Shopware\Models\Order\Detail', 'details')
                ->leftJoin('details.order', 'orders');
                        
        if (isset($filter['orderstate']) && is_numeric($filter['orderstate'])) {
            $builder->andWhere('orders.status = :orderstate');
            $builder->setParameter('orderstate', $filter['orderstate']);
        }

        if (isset($filter['paymentstate']) && is_numeric($filter['paymentstate'])) {
            $builder->andWhere('orders.cleared = :paymentstate');
            $builder->setParameter('paymentstate', $filter['paymentstate']);
        }
        
        if (isset($filter['ordernumberFrom']) && is_numeric($filter['ordernumberFrom'])) {
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
            $builder->setParameter('dateTo', $dateTo->get('yyyy-MM-dd HH:mm:ss'));
        }
        
        $builder->setFirstResult($start)
                ->setMaxResults($limit);
        
        $records = $builder->getQuery()->getResult();
        
        $result = array();
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    /**
     * Returns categories 
     * 
     * @param array $ids
     * @param array $columns
     * @return array
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                    ->get('adapters/orders/no_ids', 'Can not read orders without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                    ->get('adapters/orders/no_column_names', 'Can not read orders without column names.');
            throw new \Exception($message);
        }

        $builder = $this->getBuilder($columns, $ids);

        $orders = $builder->getQuery()->getResult();

        $orders = DbAdapterHelper::decodeHtmlEntities($orders);

        $result['default'] = DbAdapterHelper::escapeNewLines($orders);
        
        return $result;
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * Update order
     * 
     * @param array $records
     */
    public function write($records)
    {
        $records = Shopware()->Events()->filter(
                'Shopware_Components_SwagImportExport_DbAdapters_OrdersDbAdapter_Write',
                $records,
                array('subject' => $this)
        );

        foreach ($records['default'] as $index => $record) {
            try {

                if ((!isset($record['orderId']) || !$record['orderId']) && (!isset($record['number']) || !$record['number']) && (!isset($record['orderDetailId']) || !$record['orderDetailId'])) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/orders/ordernumber_order_details_requires', 'Order number or order detail id must be provided');
                    throw new AdapterException($message);
                }

                if (isset($record['orderDetailId']) && $record['orderDetailId']) {
                    $orderDetailModel = $this->getDetailRepository()->find($record['orderDetailId']);

                    if (!$orderDetailModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/order_detail_id_not_found', 'Order detail id %s was not found');
                        throw new AdapterException(sprintf($message, $record['orderDetailId']));
                    }
                } else {
                    $orderDetailModel = $this->getDetailRepository()->findOneBy(array('number' => $record['number']));

                    if (!$orderDetailModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/order_detail_id_not_found', 'Order detail id %s was not found');
                        throw new AdapterException(sprintf($message, $record['orderDetailId']));
                    }
                }

                $orderModel = $orderDetailModel->getOrder();

                if (isset($record['paymentId']) && is_numeric($record['paymentId'])) {
                    $paymentStatusModel = $this->getManager()->find('\Shopware\Models\Order\Status', $record['cleared']);

                    if (!$paymentStatusModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/payment_status_id_not_found', 'Payment status id %s was not found for order %s');
                        throw new AdapterException(sprintf($message, $record['cleared'], $orderModel->getNumber()));
                    }

                    $orderModel->setPaymentStatus($paymentStatusModel);
                }

                if (isset($record['status']) && is_numeric($record['status'])) {
                    $orderStatusModel = $this->getManager()->find('\Shopware\Models\Order\Status', $record['status']);

                    if (!$orderStatusModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/status_not_found', 'Status %s was not found for order %s');
                        throw new AdapterException(sprintf($message, $record['status'], $orderModel->getNumber()));
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

                if (isset($record['statusId']) && is_numeric($record['statusId'])) {
                    $detailStatusModel = $this->getManager()->find('\Shopware\Models\Order\DetailStatus', $record['statusId']);

                    if (!$detailStatusModel) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/orders/detail_status_not_found', 'Detail status with id %s was not found');
                        throw new AdapterException(sprintf($message, $record['statusId']));
                    }

                    $orderDetailModel->setStatus($detailStatusModel);
                }

                //prepares the attributes
                foreach ($record as $key => $value) {
                    if (preg_match('/^attribute/', $key)) {
                        $newKey = lcfirst(preg_replace('/^attribute/', '', $key));
                        $orderData['attribute'][$newKey] = $value;
                        unset($record[$key]);
                    }
                }

                if ($orderData) {
                    $orderModel->fromArray($orderData);
                }

                $this->getManager()->persist($orderModel);

                unset($orderDetailModel);
                unset($orderModel);
                unset($orderData);
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }

        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
    }

    public function getLogMessages()
    {
        return $this->logMessages;
    }

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
     * @param string $section
     * @return mix
     */
    public function getColumns($section)
    {
        $method = 'get' . ucfirst($section) . 'Columns';
        
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }
    
    public function getDefaultColumns()
    {
       $columns = array(
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
            'payment.id as paymentId',
            'payment.description as paymentDescription',
            'paymentStatus.id as paymentStatusId',
            'paymentStatus.description as paymentStatusDescription',
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
            
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.salutation as billingSalutation',
            'billing.firstName as billingFirstname',
            'billing.lastName as billingLastname',
            'billing.street as billingStreet',
            'billing.streetNumber as billingStreetnumber',
            'billing.zipCode as billingZipcode',
            'billing.city as billingCity',
            'billing.vatId as billingVatId',
            'billing.phone as billingPhone',
            'billing.fax as billingFax',
            'billingCountry.name as billingCountryName',
            'billingCountry.isoName as billingCountryen',
            'billingCountry.iso as billingCountryIso',
            
            'shipping.company as shippingCompany',
            'shipping.department as shippingDepartment',
            'shipping.salutation as shippingSalutation',
            'shipping.firstName as shippingFirstname',
            'shipping.lastName as shippingLastname',
            'shipping.street as shippingStreet',
            'shipping.streetNumber as shippingStreetnumber',
            'shipping.zipCode as shippingZipcode',
            'shipping.city as shippingCity',
            'shippingCountry.name as shippingCountryName',
            'shippingCountry.isoName as shippingCountryIsoName',
            'shippingCountry.iso as shippingCountryIso',
            
            'customer.email as email',
            'customer.groupKey as customergroup',
            'customer.newsletter as newsletter',
            'customer.affiliate as affiliate',
        );
       
        $attributesSelect = $this->getAttributes();

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    public function getAttributes()
    {
        // Attributes
        $stmt = Shopware()->Db()->query('SELECT * FROM s_order_attributes LIMIT 1');
        $attributes = $stmt->fetch();

        $attributesSelect = '';
        if ($attributes) {
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
        }

        return $attributesSelect;
    }

    /**
     * Returns order repository
     * 
     * @return \Shopware\Models\Order\Order
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Order\Order');
        }
        return $this->repository;
    }
    
    /**
     * Returns order detail repository
     * 
     * @return \Shopware\Models\Order\Detail
     */
    public function getDetailRepository()
    {
        if ($this->detailRepository === null) {
            $this->detailRepository = $this->getManager()->getRepository('Shopware\Models\Order\Detail');
        }
        return $this->detailRepository;
    }

    /**
     * Returns entity manager
     * 
     * @return \Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();

        $builder->select($columns)
                ->from('Shopware\Models\Order\Detail', 'details')
                ->leftJoin('details.order', 'orders')
                ->leftJoin('details.tax', 'taxes')
                ->leftJoin('orders.billing', 'billing')
                ->leftJoin('billing.country', 'billingCountry')
                ->leftJoin('orders.shipping', 'shipping')
                ->leftJoin('shipping.country', 'shippingCountry')
                ->leftJoin('orders.payment', 'payment')
                ->leftJoin('orders.paymentStatus', 'paymentStatus')
                ->leftJoin('orders.orderStatus', 'orderStatus')
                ->leftJoin('orders.dispatch', 'dispatch')
                ->leftJoin('orders.customer', 'customer')
                ->leftJoin('orders.attribute', 'attr')
                ->where('details.id IN (:ids)')
                ->setParameter('ids', $ids);

        return $builder;
    }

}
