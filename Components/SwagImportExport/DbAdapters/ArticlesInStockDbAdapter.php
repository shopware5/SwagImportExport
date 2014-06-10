<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesInStockDbAdapter implements DataDbAdapter
{

    /**
     * @var Shopware\Components\Model\ModelManager
     */
    protected $manager;
    
    /**
     * @var Shopware\Models\Article\Detail
     */
    protected $repository;

    public function getDefaultColumns()
    {
        return array(
            'd.number as orderNumber',
            'd.inStock as inStock',
            'a.name as name',
            'd.additionalText as additionalText',
            's.name as supplier',
        );
    }

    public function read($ids, $columns)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        
        $builder->select($columns)
                ->from('Shopware\Models\Article\Detail', 'd')
                ->leftJoin('d.article', 'a')
                ->leftJoin('a.supplier', 's')
                ->leftJoin('d.prices', 'p')
                ->where('d.id IN (:ids)')
                ->setParameter('ids', $ids);
        
        $query = $builder->getQuery();
        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        $result = $paginator->getIterator()->getArrayCopy();
        
        return $result;
    }

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        
        $builder->select('d.id')
                ->from('Shopware\Models\Article\Detail', 'd')
                ->leftJoin('d.article', 'a')
                ->leftJoin('a.supplier', 's')
                ->leftJoin('d.prices', 'p')
                ->where('d.inStock > 0')
                ->andWhere("p.customerGroupKey = 'EK'")
                ->andWhere("p.from = 1")
                ->orderBy('d.id', 'ASC');

        if (!empty($filter)) {
            $builder->addFilter($filter);
        }
        
        $builder->setFirstResult($start)
                ->setMaxResults($limit);
        
        $query = $builder->getQuery();
        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        $records = $paginator->getIterator()->getArrayCopy();
        
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
        $manager = $this->getManager();

        foreach ($records as $record) {
            
            if (empty($record['orderNumber'])) {
                //todo: log this result
                continue;
            }
            $articleDetail = $this->getRepository()->findOneBy(array("number" => $record['orderNumber']));
            
            if(!$articleDetail){
                //todo: log this result
                continue;
            }
            
            $inStock = (int) $record['inStock'];
            
            $articleDetail->setInStock($inStock);
            
            $manager->persist($articleDetail);
        }
        
        $manager->flush();
    }
    
    /**
     * Returns article detail repository
     * 
     * @return Shopware\Models\Article\Detail
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->repository;
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
