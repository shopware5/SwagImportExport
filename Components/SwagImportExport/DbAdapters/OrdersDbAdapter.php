<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

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

        if (isset($filter['orderNumberFrom']) && is_numeric($filter['orderNumberFrom'])) {
            $builder->andWhere('orders.number > :orderNumberFrom');
            $builder->setParameter('orderNumberFrom', $filter['paymentstate']);
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
            throw new \Exception('Can not read categories without ids.');
        }

        if (!$columns && empty($columns)) {
            throw new \Exception('Can not read categories without column names.');
        }
        
        $manager = $this->getManager();
        $builder = $manager->createQueryBuilder();
        
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
                ->where('details.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result['default'] = $builder->getQuery()->getResult();
        
        return $result;
    }

    /**
     * Update order
     * 
     * @param array $records
     */
    public function write($records)
    {
        foreach ($records['default'] as $index => $record) {

            if ((!isset($record['orderId']) || !$record['orderId']) && (!isset($record['number']) || !$record['number']) && (!isset($record['orderDetailId']) || !$record['orderDetailId'])) {
                throw new \Exception('Order number or order detail id must be provided');
            }

            if (isset($record['orderDetailId']) && $record['orderDetailId']) {
                $orderDetailModel = $this->getDetailRepository()->find($record['orderDetailId']);

                if (!$orderDetailModel) {
                    throw new \Exception(sprintf('Order detail id %s was not found', $record['orderDetailId']));
                }
            } else {
                $orderDetailModel = $this->getDetailRepository()->findOneBy(array('number' => $record['number']));

                if (!$orderDetailModel) {
                    throw new \Exception(sprintf('Order with number %s was not found', $record['number']));
                }
            }

            $orderModel = $orderDetailModel->getOrder();

            if (isset($record['paymentId']) && is_numeric($record['paymentId'])) {
                $paymentStatusModel = $this->getManager()->find('\Shopware\Models\Order\Status', $record['paymentId']);

                if (!$paymentStatusModel) {
                    throw new \Exception(sprintf('Payment status id %s was not found', $record['paymentId']));
                }

                $orderModel->setPaymentStatus($paymentStatusModel);
            }

            if (isset($record['status']) && is_numeric($record['status'])) {
                $orderStatusModel = $this->getManager()->find('\Shopware\Models\Order\Status', $record['status']);

                if (!$orderStatusModel) {
                    throw new \Exception(sprintf('Status %s was not found', $record['status']));
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
                    throw new \Exception(sprintf('Detail status with id %s was not found', $record['statusId']));
                }

                $orderDetailModel->setStatus($detailStatusModel);
            }

            $this->getManager()->persist($orderModel);
            unset($orderDetailModel);
            unset($orderModel);
        }

        $this->getManager()->flush();
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
       return array(
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
            'orders.clearedDate as clearedDate',
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
            'details.releaseDate as releasedate',
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
    }

    /**
     * Returns order repository
     * 
     * @return Shopware\Models\Order\Order
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
     * @return Shopware\Models\Order\Detail
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
     * @return Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

}
