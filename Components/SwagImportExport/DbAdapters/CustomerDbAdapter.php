<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DataType\CustomerDataType;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Address;
use Shopware\Components\SwagImportExport\Utils\DataHelper;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\CustomerValidator;
use Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager;
use Shopware\Models\Customer\Group;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\State;
use Shopware\Models\Shop\Shop;

class CustomerDbAdapter implements DataDbAdapter
{
    /** @var ModelManager */
    protected $manager;

    /** @var array */
    protected $customerMap;

    /** @var array */
    protected $billingMap;

    /** @var array */
    protected $shippingMap;

    /** @var mixed */
    protected $unprocessedData;

    /** @var array */
    protected $logMessages;

    /** @var string */
    protected $logState;

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var CustomerValidator */
    protected $validator;

    /** @var CustomerDataManager */
    protected $dataManager;

    /** @var \Shopware\Components\Password\Manager */
    protected $passwordManager;

    /** @var \Shopware_Components_Config */
    protected $config;

    /** @var \Enlight_Event_EventManager */
    protected $eventManager;

    /**
     * @var array
     */
    protected $defaultValues = [];

    public function __construct()
    {
        $this->manager = Shopware()->Models();
        $this->db = Shopware()->Db();
        $this->validator = new CustomerValidator();
        $this->dataManager = new CustomerDataManager();
        $this->passwordManager = Shopware()->PasswordEncoder();
        $this->config = Shopware()->Config();
        $this->eventManager = Shopware()->Events();
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        $default = [];

        $default = array_merge(
            $default,
            $this->getCustomerColumns(),
            $this->getBillingColumns(),
            $this->getShippingColumns()
        );
        
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

    /**
     * @return array
     */
    public function getCustomerColumns()
    {
        $columns = [
            'customer.id as id',
            'customer.hashPassword as password',
            'unhashedPassword',
            'customer.encoderName as encoder',
            'customer.email as email',
            'customer.active as active',
            'customer.accountMode as accountMode',
            'customer.paymentId as paymentID',
            "DATE_FORMAT(customer.firstLogin, '%Y-%m-%d') as firstLogin",
            "DATE_FORMAT(customer.lastLogin, '%Y-%m-%d') as lastLogin",
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
            'customer.number as customerNumber',
            "DATE_FORMAT(customer.birthday, '%Y-%m-%d') as birthday",
        ];

        $attributesSelect = $this->getAttributesFieldsByTableName('s_user_attributes', 'userID', 'attribute', 'attrCustomer');

        if (!empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }
        
        return $columns;
    }

    /**
     * @inheritdoc
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
        $columns = [
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.salutation as billingSalutation',
            'billing.firstName as billingFirstname',
            'billing.lastName as billingLastname',
            'billing.street as billingStreet',
            'billing.zipCode as billingZipcode',
            'billing.city as billingCity',
            'billing.phone as billingPhone',
            'billing.countryId as billingCountryID',
            'billing.stateId as billingStateID',
            'billing.vatId as ustid',
            'billing.additionalAddressLine1 as billingAdditionalAddressLine1',
            'billing.additionalAddressLine2 as billingAdditionalAddressLine2',
        ];

        $attributesSelect = $this->getAttributesFieldsByTableName('s_user_billingaddress_attributes', 'billingID', 'billingAttribute', 'attrBilling');
        
        if (!empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @return array
     */
    public function getShippingColumns()
    {
        $columns = [
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
        ];

        $attributesSelect = $this->getAttributesFieldsByTableName('s_user_shippingaddress_attributes', 'shippingID', 'shippingAttribute', 'attrShipping');

        if (!empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }
        
        return $columns;
    }

    /**
     * @param array $ids
     * @param array $columns
     * @return array
     */
    public function read($ids, $columns)
    {
        foreach ($columns as $key => $value) {
            if ($value == 'unhashedPassword') {
                unset($columns[$key]);
            }
        }

        $builder = $this->getBuilder($columns, $ids);
        $query = $builder->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->manager->createPaginator($query);
        $customers = $paginator->getIterator()->getArrayCopy();

        $result['default'] = DbAdapterHelper::decodeHtmlEntities($customers);

        return $result;
    }

    /**
     * @param integer $start
     * @param integer $limit
     * @param array $filter
     * @return array
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->manager;

        $builder = $manager->createQueryBuilder();
        $builder->select('customer.id')
                ->from(Customer::class, 'customer');

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        if (!empty($filter)) {
            $builder->addFilter($filter);
        }

        $records = $builder->getQuery()->getResult();
        $result = array_column($records, 'id');

        return $result;
    }

    /**
     * @param array $records
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function write($records)
    {
        $customerCount = 0;

        if (empty($records)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/customer/no_records', 'No customer records were found.');
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CustomerDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $defaultValues = $this->getDefaultValues();

        foreach ($records['default'] as $record) {
            try {
                $customerCount++;
                $record = $this->validator->filterEmptyString($record);

                $customer = $this->findExistingEntries($record);

                $createNewCustomer = false;
                if (!$customer instanceof Customer) {
                    $createNewCustomer = true;
                    $record = $this->dataManager->setDefaultFieldsForCreate($record, $defaultValues);
                    $this->validator->checkRequiredFieldsForCreate($record);
                    $customer = new Customer();
                }

                $this->preparePassword($record);
                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, CustomerDataType::$mapper);

                $customerData = $this->prepareCustomer($record);
                $customerData['billing'] = $this->prepareBilling($record);
                $customerData['shipping'] = $this->prepareShipping($record, $createNewCustomer, $customerData['billing']);

                $customer->fromArray($customerData);

                if (isset($customerData['subshopID'])) {
                    $shop = $this->manager->getRepository(Shop::class)->find($customerData['subshopID']);

                    if (!$shop) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/shop_not_found', 'Shop with id %s was not found');
                        throw new AdapterException(sprintf($message, $customerData['subshopID']));
                    }

                    $customer->setShop($shop);
                }

                if (isset($customerData['languageId'])) {
                    $languageSubShop = $this->manager->getRepository(Shop::class)->find($customerData['languageId']);

                    if (!$languageSubShop) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/language_shop_not_found', 'Language-Shop with id %s was not found');
                        throw new AdapterException(sprintf($message, $customerData['languageId']));
                    }

                    $customer->setLanguageSubShop($languageSubShop);
                }

                $billing = $customer->getDefaultBillingAddress();
                if (!$billing instanceof Address) {
                    $billing = new Address();
                    $billing->setCustomer($customer);
                }
                if (isset($customerData['billing']['countryId'])) {
                    $customerData['billing']['country'] = $this->manager->find(Country::class, $customerData['billing']['countryId']);
                }
                if (isset($customerData['billing']['stateId'])) {
                    $customerData['billing']['state'] = $this->manager->find(State::class, $customerData['billing']['stateId']);
                }
                $billing->fromArray($customerData['billing']);

                $shipping = $customer->getDefaultShippingAddress();
                if (!$shipping instanceof Address) {
                    $shipping = new Address();
                    $shipping->setCustomer($customer);
                }
                if (isset($customerData['shipping']['countryId'])) {
                    $customerData['shipping']['country'] = $this->manager->find(Country::class, $customerData['shipping']['countryId']);
                }
                if (isset($customerData['shipping']['stateId'])) {
                    $customerData['shipping']['state'] = $this->manager->find(State::class, $customerData['shipping']['stateId']);
                }
                $shipping->fromArray($customerData['shipping']);

                $customer->setFirstname($billing->getFirstname());
                $customer->setLastname($billing->getLastname());
                $customer->setSalutation($billing->getSalutation());
                $customer->setTitle($billing->getTitle());

                $violations = $this->manager->validate($customer);
                if ($violations->count() > 0) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/customer/no_valid_customer_entity', 'No valid user entity for email %s');
                    $message = sprintf($message, $customer->getEmail());
                    foreach ($violations as $violation) {
                        $message .= "\n" . $violation->getPropertyPath() . ': ' . $violation->getMessage();
                    }
                    throw new AdapterException($message);
                }

                $this->manager->persist($customer);
                if ($createNewCustomer) {
                    $this->manager->flush();
                }

                $customer->setDefaultBillingAddress($billing);
                $this->manager->persist($billing);
                $customer->setDefaultShippingAddress($shipping);
                $this->manager->persist($shipping);

                $this->insertCustomerAttributes($customerData, $customer->getId(), $createNewCustomer);

                if (($customerCount % 20) === 0) {
                    $this->manager->flush();
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
        $this->manager->flush();
    }

    /**
     * @param array $record
     * @return array|null|object
     * @throws AdapterException
     */
    protected function findExistingEntries(array $record)
    {
        if (isset($record['id'])) {
            $customer = $this->manager->getRepository(Customer::class)->findOneBy(['id' => $record['id']]);
        }

        if (isset($record['customerNumber'])) {
            $customer = $this->manager->getRepository(Customer::class)->findOneBy([
                'number' => $record['customerNumber']
            ]);
        }

        if (!isset($customer)) {
            $accountMode = isset($record['accountMode']) ? (int) $record['accountMode'] : 0;
            $filter = ['email' => $record['email'], 'accountMode' => $accountMode];
            if (isset($record['subshopID'])) {
                $filter['shopId'] = $record['subshopID'];
            }

            $customer = $this->manager->getRepository(Customer::class)->findBy($filter);

            //checks for multiple email address
            if (count($customer) > 0 && $customer[0]->getNumber() !== $record["customerNumber"]) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/customer/multiple_email', 'There are existing email address/es with %s. Please provide subshopID');
                throw new AdapterException(sprintf($message, $record['email']));
            }

            $customer = $customer[0];
        }

        return $customer;
    }

    /**
     * @param array $record
     * @throws AdapterException
     * @throws \Exception
     */
    protected function preparePassword(array &$record)
    {
        $passwordManager = $this->passwordManager;
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
     * @param array $record
     * @return array
     * @throws AdapterException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    protected function prepareCustomer(array &$record)
    {
        if ($this->customerMap === null) {
            $columns = $this->getCustomerColumns();

            $columns = array_merge($columns, [
                'customer.subshopID as subshopID',
                'customer.languageID as languageId',
            ]);

            foreach ($columns as $column) {
                $map = DataHelper::generateMappingFromColumns($column);
                if (empty($map)) {
                    continue;
                }
                $this->customerMap[$map[0]] = $map[1];
            }
        }

        $customerData = [];

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

        if (isset($customerData['groupKey'])) {
            $customerData['group'] = $this->manager
                    ->getRepository(Group::class)
                    ->findOneBy(['key' => $customerData['groupKey']]);
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
     * @param array $record
     * @return array
     */
    protected function prepareBilling(array &$record)
    {
        if ($this->billingMap === null) {
            $columns = $this->getBillingColumns();

            foreach ($columns as $column) {
                $map = DataHelper::generateMappingFromColumns($column);
                if (empty($map)) {
                    continue;
                }
                $this->billingMap[$map[0]] = $map[1];
            }
        }

        $billingData = [];

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
     * @param array $record
     * @param boolean $newCustomer
     * @param array $billing
     * @return array
     */
    protected function prepareShipping(array &$record, $newCustomer, array $billing)
    {
        if ($this->shippingMap === null) {
            $columns = $this->getShippingColumns();

            foreach ($columns as $column) {
                $map = DataHelper::generateMappingFromColumns($column);
                if (empty($map)) {
                    continue;
                }
                $this->shippingMap[$map[0]] = $map[1];
            }
        }

        $shippingData = [];

        //use shipping as billing
        if ($newCustomer && empty($record['shippingFirstname']) && empty($params['shippingLastname'])) {
            foreach ($this->shippingMap as $mapKey => $addressKey) {
                if (!isset($record[$mapKey]) && isset($billing[$addressKey])) {
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
     * @param array $customerData
     * @param integer $customerId
     * @param Customer|boolean $newCustomer
     * @throws \Zend_Db_Adapter_Exception
     */
    protected function insertCustomerAttributes(array $customerData, $customerId, $newCustomer)
    {
        if ($newCustomer === false) {
            return;
        }

        if (isset($customerData['attribute'])) {
            return;
        }

        $sql = "INSERT INTO s_user_attributes (userID) VALUES ({$customerId})";
        $this->db->exec($sql);
    }

    /**
     * @param integer $subShopID
     * @return integer|null
     */
    protected function preparePayment($subShopID)
    {
        //on missing shopId return defaultPaymentId
        if (!isset($subShopID) || $subShopID === '') {
            return $this->config->get('sDEFAULTPAYMENT');
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
        return $this->config->get('sDEFAULTPAYMENT');
    }

    /**
     * @param integer $subShopID
     * @return array
     */
    protected function getSubShopDefaultPaymentId($subShopID)
    {
        $query =  "SELECT `value`.value
                   FROM s_core_config_elements AS element
                   JOIN s_core_config_values AS `value` ON `value`.element_id = element.id
                   WHERE `value`.shop_id = ?
                         AND element.name = ?";
        
        return $this->db->fetchRow($query, [$subShopID, 'defaultpayment']);
    }

    /**
     * @param integer $subShopID
     * @return array
     */
    protected function getMainShopDefaultPaymentId($subShopID)
    {
        $query =  "SELECT `value`.value
                   FROM s_core_config_elements AS element
                   JOIN s_core_config_values AS `value` ON `value`.element_id = element.id
                   WHERE `value`.shop_id = (SELECT main_id FROM s_core_shops WHERE id = ?)
                         AND element.name = ?";
        
        return $this->db->fetchRow($query, [$subShopID, 'defaultpayment']);
    }

    /**
     * @param string $message
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

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
        return [
            [
                'id' => 'default',
                'name' => 'default'
            ]
        ];
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $prefixField
     * @param string $prefixSelect
     * @return array
     */
    public function getAttributesFieldsByTableName($tableName, $columnName, $prefixField, $prefixSelect)
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM $tableName");
        $columns = $stmt->fetchAll();

        $columnNames = [];
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id' && $column['Field'] != $columnName) {
                $columnNames[] = $column['Field'];
            }
        }

        $attributesSelect = [];
        foreach ($columnNames as $attribute) {
            //underscore to camel case
            //exmaple: underscore_to_camel_case -> underscoreToCamelCase
            $attribute = str_replace(' ', '', ucwords(str_replace('_', ' ', $attribute)));
            $attributesSelect[] = sprintf('%s.%s as %s%s', $prefixField, lcfirst($attribute), $prefixSelect, $attribute);
        }

        return $attributesSelect;
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
     * @param array $columns
     * @param array $ids
     * @return \Shopware\Components\Model\QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
                ->from(Customer::class, 'customer')
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
