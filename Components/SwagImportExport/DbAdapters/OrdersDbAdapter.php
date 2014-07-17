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
        $builder->select($columns['order'])
                ->from('Shopware\Models\Order\Order', 'orders')
                ->leftJoin('orders.billing', 'billing')
                ->leftJoin('billing.country', 'billingCountry')
                ->leftJoin('orders.shipping', 'shipping')
                ->leftJoin('shipping.country', 'shippingCountry')
                ->leftJoin('orders.payment', 'payment')
                ->leftJoin('orders.paymentStatus', 'paymentStatus')
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
     * Insert/Update data into db
     * 
     * @param array $records
     */
    public function write($records)
    {
        
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
            'orders.orderTime as orderTime',
            'orders.transactionId as transactionId',
            'orders.partnerId as partnerId',
            'orders.shopId as shopId',
            'orders.invoiceAmount as invoiceAmount',
            'orders.invoiceAmountNet as invoiceAmountNet',
            'orders.invoiceShipping as invoiceShipping',
            'orders.invoiceShippingNet as invoiceShippingNet',
            'orders.net as net',
            'orders.referer as referer',
            'orders.clearedDate as clearedDate',
            'orders.trackingCode as trackingCode',
            'orders.languageIso as languageIso',
            'orders.currency as currency',
            'orders.currencyFactor as currencyFactor',
            'payment.id as paymentId',
            'payment.description as paymentDescription',
            'paymentStatus.id as statusId',
            'paymentStatus.description as statusDescription',
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
            'details.articleId as articleId',
            'details.articleNumber as articleNumber',
            'details.articleName as articleName',
            'details.price as price',
            'details.quantity as quantity',
            'details.price * details.quantity as invoice',
            'details.releaseDate as releasedate',
            'taxes.tax as tax',
            'details.esdArticle as esd',
            'details.mode as mode',
        );
    }

    /**
     * Returns category repository
     * 
     * @return Shopware\Models\Category\Category
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Category\Category');
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
