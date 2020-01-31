<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\DataManagers\NewsletterDataManager;
use Shopware\Components\SwagImportExport\DataType\NewsletterDataType;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\NewsletterValidator;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Customer\Repository;
use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\ContactData;
use Shopware\Models\Newsletter\Group;

class NewsletterDbAdapter implements DataDbAdapter
{
    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /** @var bool */
    protected $errorMode;

    /**
     * @var ModelManager
     */
    protected $manager;

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

    /**
     * @var NewsletterValidator
     */
    protected $validator;

    /**
     * @var NewsletterDataManager
     */
    protected $dataManager;

    /**
     * @var array
     */
    protected $defaultValues = [];

    public function __construct()
    {
        $this->manager = Shopware()->Container()->get('models');
        $this->validator = new NewsletterValidator();
        $this->dataManager = new NewsletterDataManager();
        $this->db = Shopware()->Db();
        $this->errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        $columns = [
            'na.email as email',
            'ng.name as groupName',
            'CASE WHEN (cb.salutation IS NULL) THEN cd.salutation ELSE cb.salutation END as salutation',
            'CASE WHEN (cb.firstname IS NULL) THEN cd.firstName ELSE cb.firstname END as firstName',
            'CASE WHEN (cb.lastname IS NULL) THEN cd.lastName ELSE cb.lastname END as lastName',
            'CASE WHEN (cb.street IS NULL) THEN cd.street ELSE cb.street END as street',
            'CASE WHEN (cb.city IS NULL) THEN cd.city ELSE cb.city END as city',
            'CASE WHEN (cb.zipcode IS NULL) THEN cd.zipCode ELSE cb.zipcode END as zipCode',
            'na.lastNewsletterId as lastNewsletter',
            'na.lastReadId as lastRead',
            'c.id as userID',
            'DATE_FORMAT(na.added, \'%Y-%m-%d %H:%i:%s\') as added',
            'DATE_FORMAT(na.doubleOptinConfirmed, \'%Y-%m-%d %H:%i:%s\') as doubleOptinConfirmed',
        ];

        //removes street number for shopware 5
        if (!$this->hasAdditionalShippingAddress()) {
            $columns[] = 'CASE WHEN (cb.streetNumber IS NULL) THEN cd.streetNumber ELSE cb.streetNumber END as streetNumber';
        }

        return $columns;
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
     * @return bool
     */
    public function hasAdditionalShippingAddress()
    {
        $sql = "SHOW COLUMNS FROM `s_user_shippingaddress` LIKE 'additional_address_line1'";
        $result = $this->db->fetchRow($sql);

        return !empty($result);
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    public function read($ids, $columns)
    {
        $builder = $this->getBuilder($columns, $ids);
        $result['default'] = $builder->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * @return array
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('na.id')
            ->from(Address::class, 'na')
            ->orderBy('na.id', 'ASC');

        if (!empty($filter)) {
            $builder->addFilter($filter);
        }

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getArrayResult();

        $result = [];
        if ($records) {
            $result = array_column($records, 'id');
        }

        return $result;
    }

    /**
     * @param array $records
     *
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/newsletter/no_records',
                'No newsletter records were found.'
            );
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CategoriesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $defaultValues = $this->getDefaultValues();
        /** @var Repository $customerRepository */
        $customerRepository = $this->manager->getRepository(Customer::class);
        /** @var EntityRepository $addressRepository */
        $addressRepository = $this->manager->getRepository(Address::class);
        /** @var EntityRepository $groupRepository */
        $groupRepository = $this->manager->getRepository(Group::class);
        /** @var EntityRepository $contactDataRepository */
        $contactDataRepository = $this->manager->getRepository(ContactData::class);
        $count = 0;

        foreach ($records['default'] as $newsletterData) {
            try {
                ++$count;
                $newsletterData = $this->validator->filterEmptyString($newsletterData);
                $this->validator->checkRequiredFields($newsletterData);

                // no groupname set and is customer - we check if this recipient is already registered
                if (empty($newsletterData['groupName']) && !empty($newsletterData['userID'])) {
                    $customer = $customerRepository->find($newsletterData['userID']);

                    if (!$customer instanceof Customer) {
                        continue;
                    }
                    $recipient = $addressRepository->findOneBy(['email' => $newsletterData['email'], 'groupId' => 0, 'isCustomer' => true]);

                    if (!$recipient instanceof Address) {
                        $recipient = new Address();
                        $recipient->setEmail($newsletterData['email']);
                        $recipient->setIsCustomer(true);
                        $this->manager->persist($recipient);
                        $this->manager->flush($recipient);
                    }
                    continue;
                }

                if (empty($newsletterData['groupName'])) {
                    $newsletterData = $this->dataManager->setDefaultFieldsForCreate($newsletterData, $defaultValues);
                }

                $this->validator->validate($newsletterData, NewsletterDataType::$mapper);

                if ($newsletterData['groupName']) {
                    /** @var Group $group */
                    $group = $groupRepository->findOneBy(['name' => $newsletterData['groupName']]);

                    if (!$group instanceof Group) {
                        $group = new Group();
                        $group->setName($newsletterData['groupName']);
                        $this->manager->persist($group);
                        $this->manager->flush($group);
                    }
                    $newsletterData['groupId'] = $group->getId();
                }

                $recipient = $addressRepository->findOneBy(['email' => $newsletterData['email'], 'groupId' => $newsletterData['groupId']]);

                if (!$recipient instanceof Address) {
                    $recipient = new Address();
                }
                // save newsletter address
                $newsletterAddress = $this->prepareNewsletterAddress($newsletterData);
                $recipient->fromArray($newsletterAddress);
                $this->manager->persist($recipient);

                if ($recipient->getGroupId() !== 0) {
                    // save mail data
                    $contactData = $contactDataRepository->findOneBy(['email' => $newsletterData['email']]);
                    if (!$contactData instanceof ContactData) {
                        $contactData = new ContactData();
                        $contactData->setAdded(new \DateTime());
                        $this->manager->persist($contactData);
                    }
                    $contactData->fromArray($newsletterData);
                }

                if (($count % 20) === 0) {
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
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        if ($this->errorMode === false) {
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
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    /**
     * @param string $section
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
     * @return QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select($columns)
            ->from(Address::class, 'na')
            ->leftJoin('na.newsletterGroup', 'ng')
            ->leftJoin(ContactData::class, 'cd', Join::WITH, 'na.email = cd.email')
            ->leftJoin('na.customer', 'c', JOIN::WITH, 'na.isCustomer = 1')
            ->leftJoin('c.defaultBillingAddress', 'cb')
            ->where('na.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * @return array
     */
    protected function prepareNewsletterAddress($record)
    {
        $keys = [
            'email' => 'email',
            'userID' => 'isCustomer',
            'groupId' => 'groupId',
            'lastRead' => 'lastReadId',
            'lastNewsletter' => 'lastMailingId',
            'added' => 'added',
            'doubleOptinConfirmed' => 'doubleOptinConfirmed',
        ];

        $newsletterAddress = [];
        foreach ($keys as $oldKey => $newKey) {
            if (isset($record[$oldKey])) {
                $newsletterAddress[$newKey] = $record[$oldKey];
            }
        }

        $newsletterAddress['isCustomer'] = isset($record['userID']);

        return $newsletterAddress;
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
}
