<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\AddressValidator;
use Shopware\Models\Attribute\CustomerAddress as CustomerAddressAttribute;
use Shopware\Models\Country\Country;
use Shopware\Models\Country\State;
use Shopware\Models\Customer\Address;
use Shopware\Models\Customer\Customer;

class AddressDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var AddressValidator
     */
    private $addressValidator;

    /**
     * @var array<string>
     */
    private $logMessages;

    /**
     * @var string
     */
    private $logState;

    public function __construct(
        EntityManagerInterface $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->addressValidator = new AddressValidator();
    }

    /**
     * {@inheritdoc}
     */
    public function read($ids, $columns)
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
    public function readRecordIds($start = 0, $limit = 0, $filter = [])
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

        return $query->execute()->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultColumns()
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
            'address.additionalAddressLine1 as additional_address_line1',
            'address.additionalAddressLine2 as additional_address_line2',
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
    public function getSections()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns($section = [])
    {
        return $this->getDefaultColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function write($records)
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
    public function getUnprocessedData()
    {
        return [];
    }

    /**
     * @param string $logMessages
     *
     * @return void
     */
    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogState()
    {
        return $this->logState;
    }

    /**
     * @return Address
     */
    protected function setCountry(Address $addressModel, array $addressRecord)
    {
        if (!$addressModel->getCountry() && $addressRecord['countryID']) {
            $addressModel->setCountry($this->modelManager->find(Country::class, $addressRecord['countryID']));

            return $addressModel;
        }

        if ($addressModel->getCountry()->getId() !== $addressRecord['countryID'] && $addressRecord['countryID'] > 0) {
            $addressModel->setCountry($this->modelManager->find(Country::class, $addressRecord['countryID']));
        }

        return $addressModel;
    }

    /**
     * @return Customer|null
     */
    private function findCustomerByEmailAndNumber(array $addressRecord)
    {
        $customerRepository = $this->modelManager->getRepository(Customer::class);

        return $customerRepository->findOneBy([
            'number' => $addressRecord['customernumber'],
            'email' => $addressRecord['email'],
        ]);
    }

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
     * @return State|null
     */
    private function findStateById(array $addressRecord)
    {
        if ($addressRecord['stateID']) {
            return $this->modelManager->find(State::class, $addressRecord['stateID']);
        }

        return null;
    }

    /**
     * @return Customer
     */
    private function getCustomer(array $addressRecord)
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
     * @return array
     */
    private function getAttributeColumns()
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
     * @return CustomerAddressAttribute
     */
    private function getAttributeModel(array $addressRecord, Address $addressModel)
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
     * @param string $message
     *
     * @throws \Exception
     */
    private function saveErrorMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');
        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @param string $logState
     */
    private function setLogState($logState)
    {
        $this->logState = $logState;
    }

    private function createAddressWithId(array $addressRecord): Address
    {
        $connection = $this->modelManager->getConnection();
        $connection->executeQuery(
            'INSERT INTO s_user_addresses
              (id, user_id, country_id, zipcode, firstname, lastname)
              VALUES
              (:id, :userID, :countryID, :zipcode, :firstname, :lastname)
          ',
            $addressRecord
        );

        $addressId = $connection->lastInsertId();

        $address = $this->modelManager->find(Address::class, $addressId);
        if (!$address instanceof Address) {
            throw new \RuntimeException(sprintf('Recently created address with ID %s not found', $addressId));
        }

        return $address;
    }

    /**
     * @throws AdapterException
     *
     * @return array
     */
    private function setCustomer(Address $addressModel, array $addressRecord)
    {
        $errorMessage = SnippetsHelper::getNamespace()->get(
            'adapters/address/customer_not_found',
            'Could not find customer. Email: %s, Customernumber: %s, userID: %s'
        );

        if (!$addressModel->getCustomer()) {
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
     * @throws AdapterException
     *
     * @return array
     */
    private function setState(Address $addressModel, array $addressRecord)
    {
        if (!$addressRecord['stateID']) {
            return $addressRecord;
        }

        $errorMessage = SnippetsHelper::getNamespace()->get(
            'adapters/address/state_not_found',
            'Could not find state with stateID: %s'
        );

        $state = $this->findStateById($addressRecord);
        if (!$state) {
            throw new AdapterException(\sprintf($errorMessage, $addressRecord['stateID']));
        }

        $addressModel->setState($state);
        $addressRecord['stateID'] = $state->getId();

        return $addressRecord;
    }
}
