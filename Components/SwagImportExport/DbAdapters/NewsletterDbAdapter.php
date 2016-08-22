<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\Query\Expr\Join;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\DataType\NewsletterDataType;
use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\Group;
use Shopware\Models\Newsletter\ContactData;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\NewsletterValidator;
use Shopware\Components\SwagImportExport\DataManagers\NewsletterDataManager;

class NewsletterDbAdapter implements DataDbAdapter
{
    protected $manager;
    protected $groupRepository;
    protected $addressRepository;
    protected $contactDataRepository;

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

    /** @var NewsletterValidator */
    protected $validator;

    /**  @var NewsletterDataManager */
    protected $dataManager;

    /**
     * @var array
     */
    protected $defaultValues = array();

    public function getDefaultColumns()
    {
        $columns = array(
            'na.email as email',
            'ng.name as groupName',
            'CASE WHEN (cb.salutation IS NULL) THEN cd.salutation ELSE cb.salutation END as salutation',
            'CASE WHEN (cb.firstName IS NULL) THEN cd.firstName ELSE cb.firstName END as firstName',
            'CASE WHEN (cb.lastName IS NULL) THEN cd.lastName ELSE cb.lastName END as lastName',
            'CASE WHEN (cb.street IS NULL) THEN cd.street ELSE cb.street END as street',
            'CASE WHEN (cb.city IS NULL) THEN cd.city ELSE cb.city END as city',
            'CASE WHEN (cb.zipCode IS NULL) THEN cd.zipCode ELSE cb.zipCode END as zipCode',
            'na.lastNewsletterId as lastNewsletter',
            'na.lastReadId as lastRead',
            'c.id as userID',
        );

        //removes street number for shopware 5
        if (!$this->isAdditionalShippingAddressExists()) {
            $columns[] = 'CASE WHEN (cb.streetNumber IS NULL) THEN cd.streetNumber ELSE cb.streetNumber END as streetNumber';
        }

        return $columns;
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
     * @return bool
     */
    public function isAdditionalShippingAddressExists()
    {
        $sql = "SHOW COLUMNS FROM `s_user_shippingaddress` LIKE 'additional_address_line1'";
        $result = Shopware()->Db()->fetchRow($sql);
        return $result ? true : false;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @param $ids
     * @param $columns
     * @return mixed
     */
    public function read($ids, $columns)
    {
        $builder = $this->getBuilder($columns, $ids);

        $result['default'] = $builder->getQuery()->getResult();

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

        $builder->select('na.id')
                ->from('Shopware\Models\Newsletter\Address', 'na')
                ->orderBy('na.id', 'ASC');

        if (!empty($filter)) {
            $builder->addFilter($filter);
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

    /**
     * @param $records
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
            array('subject' => $this)
        );

        $manager = $this->getManager();
        $validator = $this->getValidator();
        $dataManager = $this->getDataManager();

        $defaultValues = $this->getDefaultValues();

        foreach ($records['default'] as $newsletterData) {
            try {
                $newsletterData = $validator->prepareInitialData($newsletterData);
                $validator->checkRequiredFields($newsletterData);

                $recipient = $this->getAddressRepository()->findOneByEmail($newsletterData['email']);
                if (!$recipient instanceof Address) {
                    $newsletterData = $dataManager->setDefaultFieldsForCreate($newsletterData, $defaultValues);
                    $recipient = new Address();
                }

                $validator->validate($newsletterData, NewsletterDataType::$mapper);

                if ($newsletterData['groupName']) {
                    $group = $this->getGroupRepository()->findOneByName($newsletterData['groupName']);

                    if (!$group instanceof Group) {
                        $group = new Group();
                        $group->setName($newsletterData['groupName']);
                        $manager->persist($group);
                        $manager->flush();
                    }

                    $newsletterData['groupId'] = $group->getId();
                }


                // save newsletter address
                $newsletterAddress = $this->prepareNewsletterAddress($newsletterData);
                $recipient->fromArray($newsletterAddress);
                $manager->persist($recipient);


                // save mail data
                $contactData = $this->getContactDataRepository()->findOneByEmail($newsletterData['email']);
                if (!$contactData instanceof ContactData) {
                    $contactData = new ContactData();
                    $contactData->setAdded(new \DateTime());
                }
                $contactData->fromArray($newsletterData);
                $manager->persist($contactData);


                $manager->flush();
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @param $record
     * @return array
     */
    protected function prepareNewsletterAddress($record)
    {
        $keys = array(
            'email' => 'email',
            'userID' => 'isCustomer',
            'groupId' => 'groupId',
            'lastRead' => 'lastReadId',
            'lastNewsletter' => 'lastMailingId',
        );

        $newsletterAddress = array();
        foreach ($keys as $oldKey => $newKey) {
            if (isset($record[$oldKey])) {
                $newsletterAddress[$newKey] = $record[$oldKey];
            }
        }

        $newsletterAddress['isCustomer'] = isset($record['userID']);

        return $newsletterAddress;
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
     * @param $columns
     * @param $ids
     * @return QueryBuilder
     */
    public function getBuilder($columns, $ids)
    {
        $builder = $this->getManager()->createQueryBuilder();

        $builder->select($columns)
                ->from('Shopware\Models\Newsletter\Address', 'na')
                ->leftJoin('na.newsletterGroup', 'ng')
                ->leftJoin('Shopware\Models\Newsletter\ContactData', 'cd', Join::WITH, 'na.email = cd.email')
                ->leftJoin('na.customer', 'c')
                ->leftJoin('c.billing', 'cb')
                ->where('na.id IN (:ids)')
                ->setParameter('ids', $ids);

        return $builder;
    }

    /**
     * Helper function to get access to the Group repository.
     * @return \Shopware\Models\Newsletter\Repository
     */
    protected function getGroupRepository()
    {
        if ($this->groupRepository === null) {
            $this->groupRepository = $this->getManager()->getRepository('Shopware\Models\Newsletter\Group');
        }
        return $this->groupRepository;
    }

    /**
     * Helper function to get access to the Address repository.
     * @return \Shopware\Models\Newsletter\Repository
     */
    protected function getAddressRepository()
    {
        if ($this->addressRepository === null) {
            $this->addressRepository = $this->getManager()->getRepository('Shopware\Models\Newsletter\Address');
        }
        return $this->addressRepository;
    }

    /**
     * Helper function to get access to the ContactData repository.
     * @return \Shopware\Components\Model\ModelRepository
     */
    protected function getContactDataRepository()
    {
        if ($this->contactDataRepository === null) {
            $this->contactDataRepository = $this->getManager()->getRepository('Shopware\Models\Newsletter\ContactData');
        }
        return $this->contactDataRepository;
    }

    /**
     * @return NewsletterValidator
     */
    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new NewsletterValidator();
        }

        return $this->validator;
    }

    /**
     * @return NewsletterDataManager
     */
    public function getDataManager()
    {
        if ($this->dataManager === null) {
            $this->dataManager = new NewsletterDataManager();
        }

        return $this->dataManager;
    }
}
