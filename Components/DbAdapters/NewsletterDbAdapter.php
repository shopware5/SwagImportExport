<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Customer\Customer;
use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\ContactData;
use Shopware\Models\Newsletter\Group;
use SwagImportExport\Components\DataManagers\NewsletterDataManager;
use SwagImportExport\Components\DataType\NewsletterDataType;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\NewsletterValidator;

class NewsletterDbAdapter implements DataDbAdapter, \Enlight_Hook, DefaultHandleable
{
    private \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    private bool $errorMode;

    private ModelManager $manager;

    /**
     * @var array<string>
     */
    private array $logMessages = [];

    private ?string $logState = null;

    private NewsletterValidator $validator;

    private NewsletterDataManager $dataManager;

    private array $defaultValues = [];

    private \Enlight_Event_EventManager $eventManager;

    public function __construct(
        ModelManager $manager,
        NewsletterDataManager $dataManager,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        \Shopware_Components_Config $config,
        \Enlight_Event_EventManager $eventManager
    ) {
        $this->manager = $manager;
        $this->validator = new NewsletterValidator();
        $this->dataManager = $dataManager;
        $this->db = $db;
        $this->errorMode = $config->get('SwagImportExportErrorMode');
        $this->eventManager = $eventManager;
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER;
    }

    public function getDefaultColumns(): array
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

        // removes street number for shopware 5
        if (!$this->hasAdditionalShippingAddress()) {
            $columns[] = 'CASE WHEN (cb.streetNumber IS NULL) THEN cd.streetNumber ELSE cb.streetNumber END as streetNumber';
        }

        return $columns;
    }

    /**
     * Set default values for fields which are empty or don't exist
     *
     * @param array<string, mixed> $values default values for nodes
     */
    public function setDefaultValues(array $values): void
    {
        $this->defaultValues = $values;
    }

    public function read(array $ids, array $columns): array
    {
        $result['default'] = $this->getBuilder($columns, $ids)->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
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
            $result = \array_column($records, 'id');
        }

        return $result;
    }

    /**
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write(array $records): void
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/newsletter/no_records',
                'No newsletter records were found.'
            );
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_CategoriesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $defaultValues = $this->getDefaultValues();
        $customerRepository = $this->manager->getRepository(Customer::class);
        $addressRepository = $this->manager->getRepository(Address::class);
        $groupRepository = $this->manager->getRepository(Group::class);
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
     * @return array<string>
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    public function getLogState(): ?string
    {
        return $this->logState;
    }

    public function getSections(): array
    {
        return [
            ['id' => 'default', 'name' => 'default '],
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

    private function hasAdditionalShippingAddress(): bool
    {
        $sql = "SHOW COLUMNS FROM `s_user_shippingaddress` LIKE 'additional_address_line1'";
        $result = $this->db->fetchRow($sql);

        return !empty($result);
    }

    /**
     * @throws \Exception
     */
    private function saveMessage(string $message): void
    {
        if ($this->errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    private function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    private function setLogState(string $logState): void
    {
        $this->logState = $logState;
    }

    /**
     * @param array<array<string>|string> $columns
     * @param array<int>                  $ids
     */
    private function getBuilder(array $columns, array $ids): QueryBuilder
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
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    private function prepareNewsletterAddress(array $record): array
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
     * Return list with default values for fields which are empty or don't exist
     *
     * @return array<mixed>
     */
    private function getDefaultValues(): array
    {
        return $this->defaultValues;
    }
}
