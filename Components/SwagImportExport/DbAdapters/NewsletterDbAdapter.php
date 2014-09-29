<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Newsletter\Address;
use Shopware\Models\Newsletter\Group;
use Shopware\Models\Newsletter\ContactData;

class NewsletterDbAdapter implements DataDbAdapter
{

    protected $manager;
    protected $groupRepository;
    protected $addressRepository;
    protected $contactDataRepository;

    public function getDefaultColumns()
    {
        return array(
            'na.email as email',
            'ng.name as groupName',
            'CASE WHEN (cb.salutation IS NULL) THEN cd.salutation ELSE cb.salutation END as salutation',
            'CASE WHEN (cb.firstName IS NULL) THEN cd.firstName ELSE cb.firstName END as firstName',
            'CASE WHEN (cb.lastName IS NULL) THEN cd.lastName ELSE cb.lastName END as lastName',
            'CASE WHEN (cb.street IS NULL) THEN cd.street ELSE cb.street END as street',
            'CASE WHEN (cb.streetNumber IS NULL) THEN cd.streetNumber ELSE cb.streetNumber END as streetNumber',
            'CASE WHEN (cb.city IS NULL) THEN cd.city ELSE cb.city END as city',
            'CASE WHEN (cb.zipCode IS NULL) THEN cd.zipCode ELSE cb.zipCode END as zipCode',
            'na.lastNewsletterId as lastNewsletter',
            'na.lastReadId as lastRead',
            'c.id as userID',
        );
    }

    public function read($ids, $columns)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Newsletter\Address', 'na')
                ->leftJoin('na.newsletterGroup', 'ng')
                ->leftJoin('Shopware\Models\Newsletter\ContactData', 'cd', \Doctrine\ORM\Query\Expr\Join::WITH, 'na.email = cd.email')
                ->leftJoin('na.customer', 'c')
                ->leftJoin('c.billing', 'cb')
                ->where('na.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result['default'] = $builder->getQuery()->getResult();

        return $result;
    }

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

    public function write($records)
    {
//        $emailValidator = new \Zend_Validate_EmailAddress();
        $manager = $this->getManager();
        
        foreach ($records['default'] as $newsletterData) {

            if (empty($newsletterData['email'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/newsletter/email_required', 'Email address is required');
                throw new \Exception($message);
            }

//            if (!$emailValidator->isValid($newsletterData['email'])) {
//                 //todo: log this result
//                continue;
//            }

            if ($newsletterData['groupName']) {
                $group = $this->getGroupRepository()->findOneByName($newsletterData['groupName']);
            }
            if (!$group && $newsletterData['groupName']) {
                $group = new Group();
                $group->setName($newsletterData['groupName']);
                $manager->persist($group);
            } elseif (!$group && $groupId = Shopware()->Config()->get("sNEWSLETTERDEFAULTGROUP")) {
                $group = $this->getGroupRepository()->findOneBy($groupId);
            } elseif (!$group) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/newsletter/group_required', 'Group is required');
                throw new \Exception($message);
            }
            
            // Create/Update the Address entry
            $recipient = $this->getAddressRepository()->findOneByEmail($newsletterData['email']);

            if (!$recipient) {
                $recipient = new Address();
            }

            $recipient->setEmail($newsletterData['email']);
            $recipient->setIsCustomer(!empty($newsletterData['userID']));

            //Only set the group if it was explicitly provided or it's a new entry
            if ($group && ($newsletterData['groupName'] || !$recipient->getId())) {
                $recipient->setNewsletterGroup($group);
            }
            $manager->persist($recipient);

            //Create/Update the ContactData entry
            $contactData = $this->getContactDataRepository()->findOneByEmail($newsletterData['email']);
            
            if (!$contactData) {
                $contactData = new ContactData();
            }
            
            $contactData->fromArray($newsletterData);
            
            //Only set the group if it was explicitly provided or it's a new entry
            if ($group && ($newsletterData['groupName'] || !$contactData->getId())) {
                $manager->persist($group);
                $manager->flush();
                
                $contactData->setGroupId($group->getId());
            }
            $contactData->setAdded(new \DateTime());

            $manager->persist($contactData);
        }
        
        $manager->flush();
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
     * @return Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    /**
     * Helper function to get access to the Group repository.
     * @return Shopware\Models\Newsletter\Repository
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
     * @return Shopware\Models\Newsletter\Repository
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
     * @return Shopware\Components\Model\ModelRepository
     */
    protected function getContactDataRepository()
    {
        if ($this->contactDataRepository === null) {
            $this->contactDataRepository = $this->getManager()->getRepository('Shopware\Models\Newsletter\ContactData');
        }
        return $this->contactDataRepository;
    }

}
