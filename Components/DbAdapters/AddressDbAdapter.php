<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use Shopware\Components\Model\Exception\ModelNotFoundException;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Attribute\CustomerAddress as CustomerAddressAttribute;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\State;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\AddressValidator;

class AddressDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    private ModelManager $modelManager;

    private AddressValidator $addressValidator;

    /**
     * @var array<string>
     */
    private array $logMessages = [];

    private ?string $logState = null;

    private \Shopware_Components_Config $config;

    public function __construct(
        ModelManager $modelManager,
        \Shopware_Components_Config $config
    ) {
        $this->modelManager = $modelManager;
        $this->addressValidator = new AddressValidator();
        $this->config = $config;
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::ADDRESS_ADAPTER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array{address: array<array<string, mixed>>}
     */
    public function read(array $ids, array $columns): array
    {
        $queryBuilder = $this->modelManager->createQueryBuilder();
        $queryBuilder->select($columns)
            ->from(Address::class, 'address')
            ->leftJoin('address.customer', 'customer')
            ->leftJoin('address.state', 'state')
            ->leftJoin('address.country', 'country')
            ->leftJoin('address.attribute', 'attribute')
            ->where('address.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);

        $addresses = $queryBuilder->getQuery()->getArrayResult();

        return [
            'address' => $addresses,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $query = $this->modelManager->getConnection()->createQueryBuilder();
        $query->select(['address.id']);
        $query->from('s_user_addresses', 'address');
        $query->orderBy('address.id');

        if ($start) {
            $query->setFirstResult($start);
        }

        if ($limit) {
            $query->setMaxResults($limit);
        }

        if (\array_key_exists('customerStreamId', $filter)) {
            $query->innerJoin(
                'address',
                's_customer_streams_mapping',
                'mapping',
                'mapping.customer_id = address.user_id AND mapping.stream_id = :streamId'
            );
            $query->setParameter(':streamId', $filter['customerStreamId']);
            unset($filter['customerStreamId']);
        }

        return $query->execute()->fetchFirstColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultColumns(): array
    {
        $defaultColumns = [
            'address.id as id',
            'address.company as company',
            'address.department as department',
            'address.salutation as salutation',
            'address.title as title',
            'address.firstname as firstname',
            'address.lastname as lastname',
            'address.street as street',
            'address.zipcode as zipcode',
            'address.city as city',
            'address.vatId as vatId',
            'address.phone as phone',
            'address.additionalAddressLine1 as additionalAddressLine1',
            'address.additionalAddressLine2 as additionalAddressLine2',
            'customer.id as userID',
            'customer.email as email',
            'customer.number as customernumber',
            'country.id as countryID',
            'state.id as stateID',
        ];

        return \array_merge($defaultColumns, $this->getAttributeColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getSections(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns(?string $section = null): array
    {
        return $this->getDefaultColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $records): void
    {
        if (empty($records['address'])) {
            throw new \Exception(SnippetsHelper::getNamespace()->get('adapters/address/no_records', 'Could not find address records.'));
        }

        try {
            foreach ($records['address'] as $addressRecord) {
                $updateAddress = false;

                $addressModel = $this->getAddressModel($addressRecord);
                if ($addressModel->getId()) {
                    $updateAddress = true;
                }

                $addressRecord = $this->addressValidator->filterEmptyString($addressRecord);
                $this->addressValidator->checkRequiredFields($addressRecord, $updateAddress);

                $addressRecord = $this->setCustomer($addressModel, $addressRecord);
                $addressRecord = $this->setState($addressModel, $addressRecord);

                if (!$updateAddress && $addressRecord['id']) {
                    $addressModel = $this->createAddressWithId($addressRecord);
                }

                $addressModel->fromArray($addressRecord);

                $this->setCountry($addressModel, $addressRecord);

                $attributeModel = $this->getAttributeModel($addressRecord, $addressModel);
                $addressModel->setAttribute($attributeModel);

                $this->modelManager->persist($addressModel);
            }
            $this->modelManager->flush();
        } catch (AdapterException $e) {
            $this->saveErrorMessage($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogState(): ?string
    {
        return $this->logState;
    }

    private function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function setCountry(Address $addressModel, array $addressRecord): Address
    {
        if (!$addressModel->getCountry() && $addressRecord['countryID']) {
            $country = $this->modelManager->find(Country::class, $addressRecord['countryID']);
            if (!$country instanceof Country) {
                throw new ModelNotFoundException(Country::class, $addressRecord['countryID']);
            }
            $addressModel->setCountry($country);

            return $addressModel;
        }

        if ($addressModel->getCountry() && $addressModel->getCountry()->getId() !== $addressRecord['countryID'] && $addressRecord['countryID'] > 0) {
            $country = $this->modelManager->find(Country::class, $addressRecord['countryID']);
            if (!$country instanceof Country) {
                throw new ModelNotFoundException(Country::class, $addressRecord['countryID']);
            }
            $addressModel->setCountry($country);
        }

        return $addressModel;
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function findCustomerByEmailAndNumber(array $addressRecord): ?Customer
    {
        return $this->modelManager->getRepository(Customer::class)->findOneBy([
            'number' => $addressRecord['customernumber'],
            'email' => $addressRecord['email'],
        ]);
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function getAddressModel(array $addressRecord): Address
    {
        $addressModel = null;

        if ($addressRecord['id']) {
            $addressModel = $this->modelManager->find(Address::class, $addressRecord['id']);
        }

        if (!$addressModel) {
            $addressModel = new Address();
        }

        return $addressModel;
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function findStateById(array $addressRecord): ?State
    {
        if ($addressRecord['stateID']) {
            return $this->modelManager->find(State::class, $addressRecord['stateID']);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function getCustomer(array $addressRecord): ?Customer
    {
        $customer = null;
        if ($addressRecord['userID']) {
            $customer = $this->modelManager->find(Customer::class, $addressRecord['userID']);
        }

        if ($customer === null) {
            $customer = $this->findCustomerByEmailAndNumber($addressRecord);
        }

        return $customer;
    }

    /**
     * @return array<string>
     */
    private function getAttributeColumns(): array
    {
        $classMetadata = $this->modelManager->getClassMetadata(CustomerAddressAttribute::class);
        $fieldNames = $classMetadata->getFieldNames();

        $attributeSelections = [];
        foreach ($fieldNames as $fieldName) {
            $attributeSelections[] = 'attribute.' . $fieldName . ' as attribute' . \ucfirst($fieldName);
        }

        return $attributeSelections;
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function getAttributeModel(array $addressRecord, Address $addressModel): CustomerAddressAttribute
    {
        $attribute = [];
        foreach ($addressRecord as $field => $value) {
            if (\strpos($field, 'attribute') === false) {
                continue;
            }

            $modelFieldName = \str_replace('attribute', '', $field);
            $modelFieldName = \lcfirst($modelFieldName);
            $attribute[$modelFieldName] = $value;
        }

        $attributeModel = new CustomerAddressAttribute();
        if ($addressModel->getAttribute() instanceof CustomerAddressAttribute) {
            $attributeModel = $addressModel->getAttribute();
        }

        return $attributeModel->fromArray($attribute);
    }

    /**
     * @throws \Exception
     */
    private function saveErrorMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');
        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->logState = 'true';
    }

    /**
     * @param array<string, mixed> $addressRecord
     */
    private function createAddressWithId(array $addressRecord): Address
    {
        $connection = $this->modelManager->getConnection();
        $preparedStatement = $connection->prepare(
            'INSERT INTO s_user_addresses
              (id, user_id, country_id, zipcode, firstname, lastname)
              VALUES
              (:id, :userID, :countryID, :zipcode, :firstname, :lastname)'
        );

        $preparedStatement->executeQuery([
            'id' => $addressRecord['id'],
            'userID' => $addressRecord['userID'],
            'countryID' => $addressRecord['countryID'],
            'zipcode' => $addressRecord['zipcode'],
            'firstname' => $addressRecord['firstname'],
            'lastname' => $addressRecord['lastname'],
        ]);

        $addressId = $connection->lastInsertId();

        $address = $this->modelManager->find(Address::class, $addressId);
        if (!$address instanceof Address) {
            throw new \RuntimeException(sprintf('Recently created address with ID %s not found', $addressId));
        }

        return $address;
    }

    /**
     * @param array<string, mixed> $addressRecord
     *
     * @throws AdapterException
     *
     * @return array<string, mixed>
     */
    private function setCustomer(Address $addressModel, array $addressRecord): array
    {
        $errorMessage = SnippetsHelper::getNamespace()->get(
            'adapters/address/customer_not_found',
            'Could not find customer. Email: %s, Customernumber: %s, userID: %s'
        );

        if (!$addressModel->getCustomer() instanceof Customer) {
            $customer = $this->getCustomer($addressRecord);
            if (!$customer) {
                throw new AdapterException(
                    \sprintf(
                        $errorMessage,
                        $addressRecord['email'],
                        $addressRecord['customernumber'],
                        $addressRecord['userID']
                    )
                );
            }

            $addressRecord['userID'] = $customer->getId();
            $addressModel->setCustomer($customer);
        }

        return $addressRecord;
    }

    /**
     * @param array<string, mixed> $addressRecord
     *
     * @throws AdapterException
     *
     * @return array<string, mixed>
     */
    private function setState(Address $addressModel, array $addressRecord): array
    {
        if (!isset($addressRecord['stateID'])) {
            return $addressRecord;
        }

        $errorMessage = SnippetsHelper::getNamespace()->get(
            'adapters/address/state_not_found',
            'Could not find state with stateID: %s'
        );

        $state = $this->findStateById($addressRecord);
        if (!$state instanceof State) {
            throw new AdapterException(\sprintf($errorMessage, $addressRecord['stateID']));
        }

        $addressModel->setState($state);
        $addressRecord['stateID'] = $state->getId();

        return $addressRecord;
    }
}
