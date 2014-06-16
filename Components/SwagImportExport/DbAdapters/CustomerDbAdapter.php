<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Customer\Customer;
use Shopware\Components\SwagImportExport\Utils\DataHelper;

class CustomerDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $repository;
    protected $billingMap;

    public function getDefaultColumns()
    {
        $default = array();

        $default = array_merge($default, $this->getCustomerColumns());

        $default = array_merge($default, $this->getBillingColumns());

        $default = array_merge($default, $this->getShippingColumns());

        return $default;
    }

    public function getCustomerColumns()
    {
        return array(
            'customer.email',
            'customer.hashPassword as password',
            'customer.encoderName as encoder',
            'customer.paymentId as paymentID',
            'customer.newsletter as newsletter',
            'customer.accountMode as accountmode',
            'customer.affiliate as affiliate',
            'customer.groupKey as customergroup',
            'customer.languageId as language',
            'customer.shopId as subshopID',
            'customer.email as email',
        );
    }

    public function getBillingColumns()
    {
        return array(
            'billing.number as customerNumber',
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.salutation as billingSalutation',
            'billing.firstName as billingFirstname',
            'billing.lastName as billingLastname',
            'billing.street as billingStreet',
            'billing.streetNumber as billingStreetnumber',
            'billing.zipCode as billingZipcode',
            'billing.city as billingCity',
            'billing.phone as billingPhone',
            'billing.fax as billingFax',
            'billing.countryId as billingCountryID',
            'billing.stateId as billingStateID',
            'billing.vatId as ustid',
        );
    }

    public function getShippingColumns()
    {
        return array(
            'shipping.company as shippingCompany',
            'shipping.department as shippingDepartment',
            'shipping.salutation as shippingSalutation',
            'shipping.firstName as shippingFirstname',
            'shipping.lastName as shippingLastname',
            'shipping.street as shippingStreet',
            'shipping.streetNumber as shippingStreetnumber',
            'shipping.zipCode as shippingZipcode',
            'shipping.city as shippingCity',
            'shipping.countryId as shippingCountryID',
            'shipping.stateId as shippingStateID',
        );
    }

    public function read($ids, $columns)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select($columns)
                ->from('\Shopware\Models\Customer\Customer', 'customer')
                ->join('customer.billing', 'billing')
                ->leftJoin('customer.shipping', 'shipping')
                ->leftJoin('customer.orders', 'orders', 'WITH', 'orders.status <> -1 AND orders.status <> 4')
                ->leftJoin('billing.attribute', 'billingAttribute')
                ->leftJoin('shipping.attribute', 'shippingAttribute')
                ->leftJoin('customer.attribute', 'attribute')
                ->groupBy('customer.id')
                ->where('customer.id IN (:ids)')
                ->setParameter('ids', $ids);

        $query = $builder->getQuery();

        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        $result = $paginator->getIterator()->getArrayCopy();

        return $result;
    }

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('customer.id')
                ->from('\Shopware\Models\Customer\Customer', 'customer');

        $builder->setFirstResult($start)
                ->setMaxResults($limit);

        if (!empty($filter)) {
            $builder->addFilter($filter);
        }

        $records = $builder->getQuery()->getResult();

        $result = array();
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    public function write($records)
    {
        $manager = $this->getManager();

        foreach ($records as $record) {

            if (!$record['email']) {
                //todo: log this result
                return;
            }

            $customer = $this->getRepository()->findOneBy(array('email' => $record['email']));

            if (!$customer) {
                $customer = new Customer();
            }

            $customerData = $this->prepareCustomer($record);

            $customerData['billing'] = $this->prepareBilling($record);

            $customerData['shipping'] = $this->prepareShipping($record);

            $customer->fromArray($customerData);

            $violations = $this->getManager()->validate($customer);
            
            if ($violations->count() > 0) {
                throw new \Exception($violations);
            }
            
            $manager->persist($customer);
        }

        $manager->flush();
    }

    protected function prepareCustomer(&$record)
    {
        if ($this->customerMap === null) {
            $columns = $this->getCustomerColumns();

            foreach ($columns as $column) {

                $map = DataHelper::generateMappingFromColumns($column);
                $this->customerMap[$map[0]] = $map[1];
            }
        }

        $customerData = array();
        foreach ($record as $key => $value) {
            if (isset($this->customerMap[$key])) {
                $customerData[$this->customerMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        $customerData['password'] = $customerData['hashPassword'];

        return $customerData;
    }

    protected function prepareBilling(&$record)
    {
        if ($this->billingMap === null) {
            $columns = $this->getBillingColumns();

            foreach ($columns as $column) {

                $map = DataHelper::generateMappingFromColumns($column);
                $this->billingMap[$map[0]] = $map[1];
            }
        }

        $billingData = array();
        foreach ($record as $key => $value) {
            if (isset($this->billingMap[$key])) {
                $billingData[$this->billingMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        return $billingData;
    }

    protected function prepareShipping(&$record)
    {
        if ($this->shippingMap === null) {
            $columns = $this->getShippingColumns();

            foreach ($columns as $column) {

                $map = DataHelper::generateMappingFromColumns($column);
                $this->shippingMap[$map[0]] = $map[1];
            }
        }

        $shippingData = array();
        foreach ($record as $key => $value) {
            if (isset($this->shippingMap[$key])) {
                $shippingData[$this->shippingMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        return $shippingData;
    }

    /**
     * Returns category repository
     * 
     * @return Shopware\Models\Customer\Customer
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Customer\Customer');
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
