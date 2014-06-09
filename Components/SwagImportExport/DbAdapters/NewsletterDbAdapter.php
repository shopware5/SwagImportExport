<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Category\Category;

class NewsletterDbAdapter implements DataDbAdapter
{
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

        $result = $builder->getQuery()->getResult();


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
