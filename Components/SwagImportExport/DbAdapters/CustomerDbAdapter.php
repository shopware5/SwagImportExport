<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DataType\CustomerDataType;
use Shopware\Models\Customer\Customer;
use Shopware\Components\SwagImportExport\Utils\DataHelper;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\CustomerValidator;
use Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager;

class CustomerDbAdapter implements DataDbAdapter
{
    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $repository;
    protected $customerMap;
    protected $billingMap;
    protected $shippingMap;

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

    protected $db;

    protected $validator;

    protected $dataManager;

    protected $passwordManager;

    /**
     * @var array
     */
    protected $defaultValues = array();

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        $default = array();

        $default = array_merge($default, $this->getCustomerColumns());
        
        $default = array_merge($default, $this->getBillingColumns());

        $default = array_merge($default, $this->getShippingColumns());
        
        return $default;
    }

    /**
     * Return list with default values for fields which are empty or don't exists
     *
     * @return array
     */
    private function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * Set default values for fields which are empty or don't exists
     *
     * @param array $values default values for nodes
     */
    public function setDefaultValues($values)
    {
        $this->defaultValues = $values;
    }

    public function getCustomerColumns()
    {
        $columns = array(
            'customer.id as id',
            'customer.hashPassword as password',
            'unhashedPassword',
            'customer.encoderName as encoder',
            'customer.email as email',
            'customer.active as active',
            'customer.accountMode as accountMode',
            'customer.paymentId as paymentID',
            'customer.firstLogin as firstLogin',
            'customer.lastLogin as lastLogin',
            'customer.sessionId as sessionId',
            'customer.newsletter as newsletter',
            'customer.validation as validation',
            'customer.affiliate as affiliate',
            'customer.groupKey as customergroup',
            'customer.paymentPreset as paymentPreset',
            'customer.languageId as language',
            'customer.shopId as subshopID',
            'customer.referer as referer',
            'customer.priceGroupId as priceGroupId',
            'customer.internalComment as internalComment',
            'customer.failedLogins as failedLogins',
            'customer.lockedUntil as lockedUntil',
        );
        
        // Attributes
        $attributes = $this->getAttributesByTableName('s_user_attributes');

        $attributesSelect = '';
        if ($attributes) {
            $prefix = 'attribute';
            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                if ($attribute === 'userID') {
                    continue;
                }
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase
                $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attrCustomer%s', $prefix, $catAttr, ucwords($catAttr));
            }
        }
        
        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }
        
        return $columns;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @return array
     */
    public function getBillingColumns()
    {
        $columns = array(
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.salutation as billingSalutation',
            'billing.number as customerNumber',
            'billing.firstName as billingFirstname',
            'billing.lastName as billingLastname',
            'billing.street as billingStreet',
            'billing.zipCode as billingZipcode',
            'billing.city as billingCity',
            'billing.phone as billingPhone',
            'billing.fax as billingFax',
            'billing.countryId as billingCountryID',
            'billing.stateId as billingStateID',
            'billing.vatId as ustid',
            'billing.birthday as birthday',
            'billing.additionalAddressLine1 as billingAdditionalAddressLine1',
            'billing.additionalAddressLine2 as billingAdditionalAddressLine2',
        );

        // Attributes
        $attributes = $this->getAttributesByTableName('s_user_billingaddress_attributes');

        $attributesSelect = '';
        if ($attributes) {
            $prefix = 'billingAttribute';
            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                if ($attribute === 'billingID') {
                    continue;
                }
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase
                $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attrBilling%s', $prefix, $catAttr, ucwords($catAttr));
            }
        }
        
        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @return array
     */
    public function getShippingColumns()
    {
        $columns = array(
            'shipping.company as shippingCompany',
            'shipping.department as shippingDepartment',
            'shipping.salutation as shippingSalutation',
            'shipping.firstName as shippingFirstname',
            'shipping.lastName as shippingLastname',
            'shipping.street as shippingStreet',
            'shipping.zipCode as shippingZipcode',
            'shipping.city as shippingCity',
            'shipping.countryId as shippingCountryID',
            'shipping.stateId as shippingStateID',
            'shipping.additionalAddressLine1 as shippingAdditionalAddressLine1',
            'shipping.additionalAddressLine2 as shippingAdditionalAddressLine2',
        );

        // Attributes
        $attributes = $this->getAttributesByTableName('s_user_shippingaddress_attributes');

        $attributesSelect = '';
        if ($attributes) {
            $prefix = 'shippingAttribute';
            $attributesSelect = array();
            
            foreach ($attributes as $attribute) {
                if ($attribute === 'shippingID') {
                    continue;
                }
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase
                $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attrShipping%s', $prefix, $catAttr, ucwords($catAttr));
            }
        }

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }
        
        return $columns;
    }

    /**
     * @param $ids
     * @param $columns
     * @return mixed
     */
    public function read($ids, $columns)
    {
        $manager = $this->getManager();
        
        foreach ($columns as $key => $value) {
            if ($value == 'unhashedPassword') {
                unset($columns[$key]);
            }
        }
        
        $builder = $this->getBuilder($columns, $ids);
        $query = $builder->getQuery();

        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        $customers = $paginator->getIterator()->getArrayCopy();
        
        $result['default'] = DbAdapterHelper::decodeHtmlEntities($customers);

        return $result;
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @return array
     */
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

    /**
     * @param $records
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function write($records)
    {
        if (empty($records)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/customer/no_records', 'No customer records were found.');
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CustomerDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        $db = $this->getDb();
        $manager = $this->getManager();
        $validator = $this->getValidator();
        $dataManager = $this->getDataManager();

        $defaultValues = $this->getDefaultValues();

        foreach ($records['default'] as $record) {
            try {
                $record = $validator->prepareInitialData($record);

                $customer = $this->findExistingEntries($record);

                if (!$customer instanceof Customer) {
                    $record = $dataManager->setDefaultFieldsForCreate($record, $defaultValues);
                    $validator->checkRequiredFieldsForCreate($record);
                    $customer = new Customer();
                }

                $this->preparePassword($record);
                $validator->checkRequiredFields($record);
                $validator->validate($record, CustomerDataType::$mapper);

                $customerData = $this->prepareCustomer($record);
                $customerData['billing'] = $this->prepareBilling($record);
                $customerData['shipping'] = $this->prepareShipping($record, $customer->getId(), $customerData['billing']);

                $customer->fromArray($customerData);

                $violations = $this->getManager()->validate($customer);
                if ($violations->count() > 0) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/customer/no_valid_customer_entity', 'No valid user entity for email %s');
                    throw new AdapterException(sprintf($message, $record['email']));
                }

                $manager->persist($customer);
                $manager->flush();

                if (isset($customerData['encoderName']) && $customerData['encoderName']) {
                    $customerId = $customer->getId();

                    $data['encoder'] = lcfirst($customerData['encoderName']);
                    $whereUser = array('id=' . $customerId);
                    $db->update('s_user', $data, $whereUser);
                }

                $manager->clear();
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @param $record
     * @return array|null|object
     * @throws AdapterException
     */
    protected function findExistingEntries($record)
    {
        if (isset($record['id'])) {
            $customer = $this->getRepository()->findOneBy(array('id' => $record['id']));
        }

        if (!$customer instanceof Customer) {
            $accountMode = isset($record['accountMode']) ? (int) $record['accountMode'] : 0;
            $filter = array('email' => $record['email'], 'accountMode' => $accountMode);
            if (isset($record['subshopID'])) {
                $filter['shopId'] = $record['subshopID'];
            }

            $customer = $this->getRepository()->findBy($filter);

            //checks for multiple email address
            if (count($customer) > 1) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/customer/multiple_email', 'There are existing email address/es with %s. Please provide subshopID');
                throw new AdapterException(sprintf($message, $record['email']));
            }

            $customer = $customer[0];
        }

        return $customer;
    }

    /**
     * @param $record
     * @throws AdapterException
     * @throws \Exception
     */
    protected function preparePassword(&$record)
    {
        $passwordManager = $this->getPasswordManager();
        if (isset($record['unhashedPassword']) && !isset($record['password'])) {
            if (!isset($record['encoder'])) {
                $record['encoder'] = $passwordManager->getDefaultPasswordEncoderName();
            }

            $encoder = $passwordManager->getEncoderByName($record['encoder']);

            $record['password'] = $encoder->encodePassword($record['unhashedPassword']);

            unset($record['unhashedPassword']);
        }

        if ((isset($record['password']) && !isset($record['encoder'])) ||
            (!isset($record['password']) && isset($record['encoder']))
        ) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/customer/password_and_encoder_required', 'Password and encoder must be provided for email %s');
            throw new AdapterException(sprintf($message, $record['email']));
        }
    }

    /**
     * @param $record
     * @return array
     * @throws AdapterException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
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

        //TODO: use validator
        if (isset($record['subshopID'])) {
            $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $record['subshopID']);

            if (!$shop) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/shop_not_found', 'Shop with id %s was not found');
                throw new AdapterException(sprintf($message, $record['subshopID']));
            }

            $customerData['shop'] = $shop;
        }

        foreach ($record as $key => $value) {
            if (preg_match('/^attrCustomer/', $key)) {
                $newKey = lcfirst(preg_replace('/^attrCustomer/', '', $key));
                $customerData['attribute'][$newKey] = $value;
                unset($record[$key]);
            } elseif (isset($this->customerMap[$key])) {
                $customerData[$this->customerMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        //TODO: use validator
        if (isset($customerData['groupKey'])) {
            $customerData['group'] = Shopware()->Models()
                    ->getRepository('Shopware\Models\Customer\Group')
                    ->findOneBy(array('key' => $customerData['groupKey']));
            if (!$customerData['group']) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/customerGroup_not_found', 'Customer Group by key %s not found');
                throw new \Exception(sprintf($message, $customerData['groupKey']));
            }
        }

        if (isset($customerData['hashPassword']) && !empty($customerData['hashPassword'])) {
            $customerData['rawPassword'] = $customerData['hashPassword'];
        }

        unset($record['hashPassword']);

        return $customerData;
    }

    /**
     * @param $record
     * @return array
     */
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
            //prepares the attributes
            if (preg_match('/^attrBilling/', $key)) {
                $newKey = lcfirst(preg_replace('/^attrBilling/', '', $key));
                $billingData['attribute'][$newKey] = $value;
                unset($record[$key]);
            } elseif (isset($this->billingMap[$key])) {
                $billingData[$this->billingMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        return $billingData;
    }

    /**
     * @param $record
     * @param $customerId
     * @param $billing
     * @return array
     */
    protected function prepareShipping(&$record, $customerId, $billing)
    {
        if ($this->shippingMap === null) {
            $columns = $this->getShippingColumns();

            foreach ($columns as $column) {
                $map = DataHelper::generateMappingFromColumns($column);
                $this->shippingMap[$map[0]] = $map[1];
            }
        }

        $shippingData = array();

        //use shipping as billing
        if (empty($customerId) && empty($record['shippingFirstname']) && empty($params['shippingLastname'])) {
            foreach ($this->shippingMap as $mapKey => $addressKey) {
                if (isset($record[$mapKey])) {
                    $shippingData[$addressKey] = $billing[$addressKey];
                    unset($record[$mapKey]);
                }
            }

            return $shippingData;
        }

        foreach ($record as $key => $value) {
            //prepares the attributes
            if (preg_match('/^attrShipping/', $key)) {
                $newKey = lcfirst(preg_replace('/^attrShipping/', '', $key));
                $shippingData['attribute'][$newKey] = $value;
                unset($record[$key]);
            } elseif (isset($this->shippingMap[$key])) {
                $shippingData[$this->shippingMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        return $shippingData;
    }

    /**
     * @param $subShopID
     * @return mixed|null
     */
    protected function preparePayment($subShopID)
    {
        //on missing shopId return defaultPaymentId
        if (!isset($subShopID) || $subShopID === '') {
            return Shopware()->Config()->get('sDEFAULTPAYMENT');
        }
        
        //get defaultPaymentId for subShiopId = $subShopID
        $defaultPaymentId = $this->getSubShopDefaultPaymentId($subShopID);
        if ($defaultPaymentId) {
            return unserialize($defaultPaymentId['value']);
        }
        
        //get defaultPaymentId for mainShiopId
        $defaultPaymentId = $this->getMainShopDefaultPaymentId($subShopID);
        if ($defaultPaymentId) {
            return unserialize($defaultPaymentId['value']);
        }
        return Shopware()->Config()->get('sDEFAULTPAYMENT');
    }

    /**
     * @param $subShopID
     * @return mixed
     */
    protected function getSubShopDefaultPaymentId($subShopID)
    {
        $query =  "SELECT `value`.value
                   FROM s_core_config_elements AS element
                   JOIN s_core_config_values AS `value` ON `value`.element_id = element.id
                   WHERE `value`.shop_id = ?
                         AND element.name = ?";
        
        return Shopware()->Db()->fetchRow($query, array($subShopID, 'defaultpayment'));
    }

    /**
     * @param $subShopID
     * @return mixed
     */
    protected function getMainShopDefaultPaymentId($subShopID)
    {
        $query =  "SELECT `value`.value
                   FROM s_core_config_elements AS element
                   JOIN s_core_config_values AS `value` ON `value`.element_id = element.id
                   WHERE `value`.shop_id = (SELECT main_id FROM s_core_shops WHERE id = ?)
                         AND element.name = ?";
        
        return Shopware()->Db()->fetchRow($query, array($subShopID, 'defaultpayment'));
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
     * @param $logMessages
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
            array('id' => 'default', 'name' => 'default ')
        );
    }

    /**
     * @param $tableName
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAttributesByTableName($tableName)
    {
        $stmt = $this->getDb()->query("SHOW COLUMNS FROM $tableName");
        $columns = $stmt->fetchAll();

        $columnNames = array();
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id') {
                $columnNames[] = $column['Field'];
            }
        }

        return $columnNames;
    }

    /**
     * @param string $section
     * @return bool|mixed
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
     * Returns category repository
     *
     * @return \Shopware\Models\Customer\Repository
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
     * @return CustomerValidator
     */
    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new CustomerValidator();
        }

        return $this->validator;
    }

    /**
     * @return CustomerDataManager
     */
    public function getDataManager()
    {
        if ($this->dataManager === null) {
            $this->dataManager = new CustomerDataManager();
        }

        return $this->dataManager;
    }

    /**
     * @return \Shopware\Components\Password\Manager
     */
    public function getPasswordManager()
    {
        if ($this->passwordManager === null) {
            $this->passwordManager = Shopware()->PasswordEncoder();
        }

        return $this->passwordManager;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();
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

        return $builder;
    }
}
