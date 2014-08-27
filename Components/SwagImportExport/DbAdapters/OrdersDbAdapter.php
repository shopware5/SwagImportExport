<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class OrdersDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * Shopware\Models\Category\Category
     */
    protected $repository;

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

        $builder->select('orders.id')
                ->from('Shopware\Models\Order\Order', 'orders');
                        
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
    
    public function getParentKeys($section)
    {
        switch ($section) {
            case 'order':
                return array(
                    'orders.id as orderId',
//                    'orders.number as number',
                );
            case 'detail':
                return array(
                    'details.orderId as orderId',
                );
        }
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
        $builder->select($columns['order'])
                ->from('Shopware\Models\Order\Order', 'orders')
                ->leftJoin('orders.billing', 'billing')
                ->leftJoin('billing.country', 'billingCountry')
                ->leftJoin('orders.shipping', 'shipping')
                ->leftJoin('shipping.country', 'shippingCountry')
                ->leftJoin('orders.payment', 'payment')
                ->leftJoin('orders.paymentStatus', 'paymentStatus')
                ->leftJoin('orders.orderStatus', 'orderStatus')
                ->leftJoin('orders.dispatch', 'dispatch')
                ->leftJoin('orders.customer', 'customer')
                ->where('orders.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result['order'] = $builder->getQuery()->getResult();
        
        //details
        $datailsBuilder = $manager->createQueryBuilder();
        $datailsBuilder->select($columns['detail'])
                ->from('Shopware\Models\Order\Order', 'orders')
                ->leftJoin('orders.details', 'details')
                ->leftJoin('details.tax', 'taxes')
                ->where('orders.id IN (:ids)')
                ->setParameter('ids', $ids);
        $result['detail'] = $datailsBuilder->getQuery()->getResult();
        
        return $result;
    }

    /**
     * Returns default categories columns name 
     * and category attributes
     * 
     * @return array
     */
    public function getDefaultColumns()
    {
        return array(
            'order' => $this->getOrderColumns(),
            'detail' => $this->getDetailColumns(),
        );
    }

    /**
     * Update order
     * 
     * @param array $records
     */
    public function write($records)
    {
        foreach ($records['order'] as $index => $record) {

            if ((!isset($record['orderId']) || !$record['orderId']) && (!isset($record['number']) || !$record['number'])) {
                throw new \Exception('Order id or order number must be provided');
            }

            if (isset($record['orderId']) && $record['orderId']) {
                $orderModel = $this->getRepository()->find($record['orderId']);

                if (!$orderModel) {
                    throw new \Exception(sprintf('Order with id %s was not found', $record['orderId']));
                }
            } else {
                $orderModel = $this->getRepository()->findOneBy(array('number' => $record['number']));

                if (!$orderModel) {
                    throw new \Exception(sprintf('Order with number %s was not found', $record['number']));
                }
            }

            if (isset($record['paymentId']) && is_numeric($record['status'])) {
                $paymentStatusModel = $this->getManager()->find('\Shopware\Models\Order\Status', $record['paymentId']);

                if (!$paymentStatusModel) {
                    throw new \Exception(sprintf('Payment status id %s was not found', $record['paymentId']));
                }

                $orderModel->setPaymentStatus($paymentStatusModel);
            }

            if (isset($record['status']) && is_numeric($record['status'])) {
                $orderStatusModel = $this->getManager()->find('\Shopware\Models\Order\Status', $record['status']);

                if (!$orderStatusModel) {
                    throw new \Exception(sprintf('Payment status id %s was not found', $record['status']));
                }

                $orderModel->setOrderStatus($orderStatusModel);
            }

            if (isset($record['trackingCode']) && $record['trackingCode']) {
                $orderModel->setTrackingCode($record['trackingCode']);
            }

            if (isset($record['comment']) && $record['comment']) {
                $orderModel->setComment($record['comment']);
            }

            if (isset($record['customerComment']) && $record['customerComment']) {
                $orderModel->setCustomerComment($record['customerComment']);
            }

            if (isset($record['internalComment']) && $record['internalComment']) {
                $orderModel->setInternalComment($record['internalComment']);
            }

            if (isset($record['transactionId']) && $record['transactionId']) {
                $orderModel->setTransactionId($record['transactionId']);
            }

            if (isset($record['clearedDate']) && $record['clearedDate']) {
                $orderModel->setClearedDate($record['clearedDate']);
            }

            $this->updateDetails($records['detail'], $index);

            $this->getManager()->persist($orderModel);
        }

        $this->getManager()->flush();
    }

    public function updateDetails(&$data, $detailIndex)
    {
        if ($data == null) {
            return;
        }

        foreach ($data as $key => $detailData) {

            if ($detailData['parentIndexElement'] === $detailIndex) {

                if (!isset($detailData['orderDetailId'])) {
                    throw new \Exception('Order detail id must be provided.');
                }

                $detailModel = $this->getManager()->find(
                        'Shopware\Models\Order\Detail', (int) $detailData['orderDetailId']
                );

                if (!$detailModel) {
                    throw new \Exception(sprintf('Order detail with id %s was not found', $detailData['orderDetailId']));
                }

                if (isset($detailData['shipped']) && $detailData['shipped']) {
                    $detailModel->setShipped($detailData['shipped']);
                }

                if (isset($detailData['statusId']) && is_numeric($detailData['statusId'])) {
                    $detailStatusModel = $this->getManager()->find('\Shopware\Models\Order\DetailStatus', $detailData['statusId']);

                    if (!$detailStatusModel) {
                        throw new \Exception(sprintf('Detail status with id %s was not found', $detailData['statusId']));
                    }

                    $detailModel->setStatus($detailStatusModel);
                }
            }

            $this->getManager()->persist($detailModel);
            unset($data[$key]);
            unset($detailModel);
        }
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'order', 'name' => 'order'),
            array('id' => 'detail', 'name' => 'detail')
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
    
    public function getOrderColumns()
    {
       return array(
            'orders.id as orderId',
            'orders.number as number',
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
    
    public function getDetailColumns()
    {
        return array(
            'details.id as orderDetailId',
            'details.orderId as orderId',
            'details.articleId as articleId',
            'details.taxId as taxId',
            'details.taxRate as taxRate',
            'details.statusId as statusId',
            'details.articleNumber as articleNumber',
            'details.number as number',
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
