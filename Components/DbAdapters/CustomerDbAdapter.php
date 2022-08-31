<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\Password\Manager;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\State;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Group;
use Shopware\Models\Shop\Shop;
use SwagImportExport\Components\DataManagers\CustomerDataManager;
use SwagImportExport\Components\DataType\CustomerDataType;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseServiceInterface;
use SwagImportExport\Components\Utils\DataHelper;
use SwagImportExport\Components\Utils\DbAdapterHelper;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\CustomerValidator;

class CustomerDbAdapter implements DataDbAdapter, \Enlight_Hook, DefaultHandleable
{
    protected ModelManager $manager;

    private ?array $customerMap = null;

    private ?array $billingMap = null;

    private ?array $shippingMap = null;

    private array $logMessages = [];

    private ?string $logState = null;

    private \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    private CustomerValidator $validator;

    private CustomerDataManager $dataManager;

    private Manager $passwordManager;

    private \Shopware_Components_Config $config;

    private \Enlight_Event_EventManager $eventManager;

    private array $defaultValues = [];

    private UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService;

    public function __construct(
        ModelManager $manager,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        CustomerDataManager $dataManager,
        Manager $passwordManager,
        \Shopware_Components_Config $config,
        \Enlight_Event_EventManager $eventManager,
        UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService
    ) {
        $this->manager = $manager;
        $this->db = $db;
        $this->dataManager = $dataManager;
        $this->passwordManager = $passwordManager;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->underscoreToCamelCaseService = $underscoreToCamelCaseService;

        $this->validator = new CustomerValidator();
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::CUSTOMER_ADAPTER;
    }

    public function getDefaultColumns(): array
    {
        return \array_merge(
            [],
            $this->getCustomerColumns(),
            $this->getBillingColumns(),
            $this->getShippingColumns()
        );
    }

    /**
     * Set default values for fields which are empty or don't exist
     *
     * @param array<string, array<string, mixed>> $values default values for nodes
     */
    public function setDefaultValues(array $values): void
    {
        $this->defaultValues = $values;
    }

    public function read(array $ids, array $columns): array
    {
        foreach ($columns as $key => $value) {
            if ($value === 'unhashedPassword') {
                unset($columns[$key]);
            }
        }

        $query = $this->getBuilder($columns, $ids)->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $customers = $this->manager->createPaginator($query)->getIterator()->getArrayCopy();

        $result['default'] = DbAdapterHelper::decodeHtmlEntities($customers);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $query = $this->manager->getConnection()->createQueryBuilder();
        $query->select(['customer.id']);
        $query->from('s_user', 'customer');

        if ($start) {
            $query->setFirstResult($start);
        }

        if ($limit) {
            $query->setMaxResults($limit);
        }

        if (\array_key_exists('customerStreamId', $filter)) {
            $query->innerJoin(
                'customer',
                's_customer_streams_mapping',
                'mapping',
                'mapping.customer_id = customer.id AND mapping.stream_id = :streamId'
            );
            $query->setParameter(':streamId', $filter['customerStreamId']);
        }

        $ids = $query->execute()->fetchFirstColumn();

        return \array_map('\intval', $ids);
    }

    /**
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     * @throws \Zend_Db_Adapter_Exception
     */
    public function write(array $records): void
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
                ++$customerCount;
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
                        throw new AdapterException(\sprintf($message, $customerData['subshopID']));
                    }

                    $customer->setShop($shop);
                }

                if (isset($customerData['languageId'])) {
                    $languageSubShop = $this->manager->getRepository(Shop::class)->find($customerData['languageId']);

                    if (!$languageSubShop) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/language_shop_not_found', 'Language-Shop with id %s was not found');
                        throw new AdapterException(\sprintf($message, $customerData['languageId']));
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
                    $message = \sprintf($message, $customer->getEmail());
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

    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    /**
     * @return ?string
     */
    public function getLogState(): ?string
    {
        return $this->logState;
    }

    public function getSections(): array
    {
        return [
            [
                'id' => 'default',
                'name' => 'default',
            ],
        ];
    }

    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * @return array<string>
     */
    private function getCustomerColumns(): array
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
            $columns = \array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    private function getBillingColumns(): array
    {
        $columns = [
            'billing.company as billingCompany',
            'billing.department as billingDepartment',
            'billing.salutation as billingSalutation',
            'billing.firstname as billingFirstname',
            'billing.lastname as billingLastname',
            'billing.street as billingStreet',
            'billing.zipcode as billingZipcode',
            'billing.city as billingCity',
            'billing.phone as billingPhone',
            'billing.countryId as billingCountryID',
            'billing.stateId as billingStateID',
            'billing.vatId as ustid',
            'billing.additionalAddressLine1 as billingAdditionalAddressLine1',
            'billing.additionalAddressLine2 as billingAdditionalAddressLine2',
        ];

        $attributesSelect = $this->getAttributesFieldsByTableName('s_user_addresses_attributes', 'address_id', 'billingAttribute', 'attrBilling');

        if (!empty($attributesSelect)) {
            $columns = \array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    private function getShippingColumns(): array
    {
        $columns = [
            'shipping.company as shippingCompany',
            'shipping.department as shippingDepartment',
            'shipping.salutation as shippingSalutation',
            'shipping.firstname as shippingFirstname',
            'shipping.lastname as shippingLastname',
            'shipping.street as shippingStreet',
            'shipping.zipcode as shippingZipcode',
            'shipping.city as shippingCity',
            'shipping.countryId as shippingCountryID',
            'shipping.stateId as shippingStateID',
            'shipping.additionalAddressLine1 as shippingAdditionalAddressLine1',
            'shipping.additionalAddressLine2 as shippingAdditionalAddressLine2',
        ];

        $attributesSelect = $this->getAttributesFieldsByTableName('s_user_addresses_attributes', 'address_id', 'shippingAttribute', 'attrShipping');

        if (!empty($attributesSelect)) {
            $columns = \array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @throws \Exception
     */
    private function saveMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages([$message]);
        $this->setLogState('true');
    }

    /**
     * @param array<string> $logMessages
     */
    private function setLogMessages(array $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    private function setLogState(?string $logState): void
    {
        $this->logState = $logState;
    }

    private function getAttributesFieldsByTableName(string $tableName, string $columnName, string $prefixField, string $prefixSelect): array
    {
        $columns = $this->db->query("SHOW COLUMNS FROM $tableName")->fetchAll();

        $columnNames = [];
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id' && $column['Field'] != $columnName) {
                $columnNames[] = $column['Field'];
            }
        }

        $attributesSelect = [];
        foreach ($columnNames as $attribute) {
            $attribute = $this->underscoreToCamelCaseService->underscoreToCamelCase($attribute);
            $attributesSelect[] = \sprintf('%s.%s as %s%s', $prefixField, \lcfirst($attribute), $prefixSelect, $attribute);
        }

        return $attributesSelect;
    }

    /**
     * @param array<array<string>|string> $columns
     * @param array<int>                  $ids
     */
    private function getBuilder(array $columns, array $ids): QueryBuilder
    {
        $builder = $this->manager->createQueryBuilder();
        $builder->select($columns)
                ->from(Customer::class, 'customer')
                ->join('customer.defaultBillingAddress', 'billing')
                ->leftJoin('customer.defaultShippingAddress', 'shipping')
                ->leftJoin('customer.orders', 'orders', 'WITH', 'orders.status <> -1 AND orders.status <> 4')
                ->leftJoin('billing.attribute', 'billingAttribute')
                ->leftJoin('shipping.attribute', 'shippingAttribute')
                ->leftJoin('customer.attribute', 'attribute')
                ->groupBy('customer.id')
                ->where('customer.id IN (:ids)')
                ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function findExistingEntries(array $record): ?Customer
    {
        $customer = null;
        if (isset($record['id'])) {
            $customer = $this->manager->getRepository(Customer::class)->findOneBy(['id' => $record['id']]);
        }

        if (isset($record['customerNumber'])) {
            $customer = $this->manager->getRepository(Customer::class)->findOneBy([
                'number' => $record['customerNumber'],
            ]);
        }

        if ($customer instanceof Customer) {
            return $customer;
        }

        $accountMode = isset($record['accountMode']) ? (int) $record['accountMode'] : 0;
        $filter = ['email' => $record['email'], 'accountMode' => $accountMode];
        if (isset($record['subshopID'])) {
            $filter['shopId'] = $record['subshopID'];
        }

        $customer = $this->manager->getRepository(Customer::class)->findBy($filter);

        // checks for multiple email address
        if (\count($customer) > 0 && (isset($record['customerNumber']) && $customer[0]->getNumber() !== $record['customerNumber'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/customer/multiple_email', 'There are existing email address/es with %s having different customer numbers. Please provide subshopID or equalize customer number');
            throw new AdapterException(\sprintf($message, $record['email']));
        }

        return $customer[0] ?? null;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function preparePassword(array &$record): void
    {
        $passwordManager = $this->passwordManager;
        if (isset($record['unhashedPassword']) && !isset($record['password'])) {
            if (!isset($record['encoder'])) {
                $record['encoder'] = $passwordManager->getDefaultPasswordEncoderName();
            }

            $record['password'] = $passwordManager->getEncoderByName($record['encoder'])->encodePassword($record['unhashedPassword']);

            unset($record['unhashedPassword']);
        }

        if ((isset($record['password']) && !isset($record['encoder']))
            || (!isset($record['password']) && isset($record['encoder']))
        ) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/customer/password_and_encoder_required', 'Password and encoder must be provided for email %s');
            throw new AdapterException(\sprintf($message, $record['email']));
        }
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string|int, mixed>
     */
    private function prepareCustomer(array &$record): array
    {
        if ($this->customerMap === null) {
            $columns = $this->getCustomerColumns();

            array_push($columns, 'customer.subshopID as subshopID', 'customer.languageID as languageId');

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
            if (strpos($key, 'attrCustomer') === 0) {
                $newKey = \lcfirst((string) \preg_replace('/^attrCustomer/', '', $key));
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
                throw new \RuntimeException(\sprintf($message, $customerData['groupKey']));
            }
        }

        if (!empty($customerData['hashPassword'])) {
            $customerData['rawPassword'] = $customerData['hashPassword'];
        }

        unset($record['hashPassword']);

        return $customerData;
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string|int, mixed>
     */
    private function prepareBilling(array &$record): array
    {
        if ($this->billingMap === null) {
            foreach ($this->getBillingColumns() as $column) {
                $map = DataHelper::generateMappingFromColumns($column);
                if (empty($map)) {
                    continue;
                }
                $this->billingMap[$map[0]] = $map[1];
            }
        }

        $billingData = [];

        foreach ($record as $key => $value) {
            // prepares the attributes
            if (strpos($key, 'attrBilling') === 0) {
                $newKey = \lcfirst((string) \preg_replace('/^attrBilling/', '', $key));
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
     * @param array<string, mixed>     $record
     * @param array<int|string, mixed> $billing
     *
     * @return array<int|string, mixed>
     */
    private function prepareShipping(array &$record, bool $newCustomer, array $billing): array
    {
        if ($this->shippingMap === null) {
            foreach ($this->getShippingColumns() as $column) {
                $map = DataHelper::generateMappingFromColumns($column);
                if (empty($map)) {
                    continue;
                }
                $this->shippingMap[$map[0]] = $map[1];
            }
        }

        $shippingData = [];

        // use shipping as billing
        if ($newCustomer && empty($record['shippingFirstname']) && empty($record['shippingLastname'])) {
            if (!\is_array($this->shippingMap)) {
                throw new \Exception('ShippingMap is not set');
            }

            foreach ($this->shippingMap as $mapKey => $addressKey) {
                if (!isset($record[$mapKey]) && isset($billing[$addressKey])) {
                    $shippingData[$addressKey] = $billing[$addressKey];
                    unset($record[$mapKey]);
                }
            }

            return $shippingData;
        }

        foreach ($record as $key => $value) {
            // prepares the attributes
            if (strpos($key, 'attrShipping') === 0) {
                $newKey = \lcfirst((string) \preg_replace('/^attrShipping/', '', $key));
                $shippingData['attribute'][$newKey] = $value;
                unset($record[$key]);
            } elseif (isset($this->shippingMap[$key])) {
                $shippingData[$this->shippingMap[$key]] = $value;
                unset($record[$key]);
            }
        }

        return $shippingData;
    }

    private function insertCustomerAttributes(array $customerData, int $customerId, bool $newCustomer): void
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
     * Return list with default values for fields which are empty or don't exist
     */
    private function getDefaultValues(): array
    {
        return $this->defaultValues;
    }
}
