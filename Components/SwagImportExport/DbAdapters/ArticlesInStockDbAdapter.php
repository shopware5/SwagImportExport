<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;

class ArticlesInStockDbAdapter implements DataDbAdapter
{

    /**
     * @var Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $unprocessedData;

    /**
     * @var Shopware\Models\Article\Detail
     */
    protected $repository;

    public function getDefaultColumns()
    {
        return array(
            'variant.number as orderNumber',
            'variant.inStock as inStock',
            'article.name as name',
            'variant.additionalText as additionalText',
            'articleSupplier.name as supplier',
            'prices.price as price',
        );
    }

    public function read($ids, $columns)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        
        //prices
        $columns = array_merge(
                $columns, array('customerGroup.taxInput as taxInput', 'articleTax.tax as tax')
        );
        $builder->select($columns)
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->leftJoin('variant.article', 'article')
                ->leftJoin('article.supplier', 'articleSupplier')
                ->leftJoin('variant.prices', 'prices')
                ->leftJoin('prices.customerGroup', 'customerGroup')
                ->leftJoin('article.tax', 'articleTax')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', $ids);
        
        $query = $builder->getQuery();
        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        $result['default'] = $paginator->getIterator()->getArrayCopy();
        
        foreach ($result['default'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = $record['price'] * (100 + $record['tax']) / 100; 
            }
        }
        
        return $result;
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        
        $builder->select('d.id')
                ->from('Shopware\Models\Article\Detail', 'd')
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

        foreach ($records['default'] as $record) {
            
            if (empty($record['orderNumber'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/ordernumber_required', 'Order number is required');
                throw new \Exception($message);
            }
            $articleDetail = $this->getRepository()->findOneBy(array("number" => $record['orderNumber']));
            
            if(!$articleDetail){
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articlesImages/article_not_found', 'Article with number %s does not exists.');
                throw new \Exception(sprintf($message, $record['orderNumber']));
            }
            
            $inStock = (int) $record['inStock'];
            
            $articleDetail->setInStock($inStock);
            
            $manager->persist($articleDetail);
        }
        
        $manager->flush();
        $manager->clear();
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
