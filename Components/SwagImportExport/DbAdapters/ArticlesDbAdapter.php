<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * Shopware\Models\Article\Article
     */
    protected $repository;
    protected $articleMap;
    protected $variantMap;

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('detail.id');

        $builder->from('Shopware\Models\Article\Detail', 'detail')
                ->orderBy('detail.articleId', 'ASC')
                ->orderBy('detail.kind', 'ASC');

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

    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            throw new \Exception('Can not read articles without ids.');
        }

        if (!$columns && empty($columns)) {
            throw new \Exception('Can not read articles without column names.');
        }

        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Article\Detail', 'variants')
                ->leftJoin('variants.article', 'article')
                ->leftJoin('article.supplier', 'supplier')
                ->leftJoin('article.esds', 'articleEsd')
                ->leftJoin('variants.prices', 'prices')
                ->leftJoin('variants.unit', 'variantsUnit')
                ->leftJoin('article.tax', 'articleTax')
                ->where('variants.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result = $builder->getQuery()->getResult();

        return $result;
    }

    public function getDefaultColumns()
    {
        $defaultColumns = array(
            'variantsUnit.unit as unit',
            'prices.price as netPrice',
            'prices.pseudoPrice as pseudoPrice',
            'prices.basePrice as basePrice',
            'articleEsd.file as esd',
            'supplier.name as supplierName',
        );

        $articleColumns = $this->getArticleColumns();
        $variantColumns = $this->getVariantColumns();

        return array_merge($defaultColumns, $articleColumns, $variantColumns);
    }

    public function write($records)
    {
        foreach ($records as $record) {

            if ($record['articleId']) {
                
            }

            $article = $this->getManager()->find('Shopware\Models\Article\Article', $record['articleId']);

            if (!$article) {
                $article = new Shopware\Models\Article\Article();
            }

            $variant = $this->getManager()->find('Shopware\Models\Article\Detail', $record['variantId']);

            if (!$variant) {
                $variant = new Shopware\Models\Article\Detail();
            }
            $articleData = $this->prerpareArticle($record);
            
            $variantData = $this->prerpareVariant($record);
            
            $articleData['mainDetail'] = $variant->fromArray($variantData);

            $article->fromArray($articleData);
            
            if ($violations->count() > 0) {
                throw new ApiException\ValidationException($violations);
            }

            $this->getManager()->persist($article);
            $this->flush();
        }
    }

    public function prerpareArticle(&$data)
    {
        $article = array();

        $articleMap = $this->getArticleMap();
        foreach ($data as $key => $value) {
            if (isset($articleMap[$key])) {
                $article[$articleMap[$key]] = $value;
                unset($data[$key]);
            }
        }
        
        //check if a tax id is passed and load the tax model or set the tax parameter to null.
        if (!empty($article['taxId'])) {
            $article['tax'] = $this->getManager()->find('Shopware\Models\Tax\Tax', $article['taxId']);

            if (empty($article['tax'])) {
                throw new \Exception(sprintf("Tax by id %s not found", $article['taxId']));
            }
        } elseif (!empty($article['tax'])) {
            $tax = $this->getManager()->getRepository('Shopware\Models\Tax\Tax')->findOneBy(array('tax' => $article['tax']));
            if (!$tax) {
                throw new \Exception(sprintf("Tax by taxrate %s not found", $article['tax']));
            }
            $article['tax'] = $tax;
        } else {
            unset($article['tax']);
        }

        //check if a supplier id is passed and load the supplier model or set the supplier parameter to null.
        if (!empty($article['supplierId'])) {
            $article['supplier'] = $this->getManager()->find('Shopware\Models\Article\Supplier', $article['supplierId']);
            if (empty($article['supplier'])) {
                throw new \Exception(sprintf("Supplier by id %s not found", $article['supplierId']));
            }
        } elseif (!empty($article['supplier'])) {
            $supplier = $this->getManager()->getRepository('Shopware\Models\Article\Supplier')->findOneBy(array('name' => $article['supplier']));
            if (!$supplier) {
                $supplier = new \Shopware\Models\Article\Supplier();
                $supplier->setName($article['supplier']);
            }
            $article['supplier'] = $supplier;
        } else {
            unset($article['supplier']);
        }
        
        return $article;
    }

    public function prerpareVariant(&$data)
    {
        $variant = array();

        $variantsMap = $this->getVariantsMap();

        foreach ($data as $key => $value) {
            if (isset($variantsMap[$key])) {
                $variant[$variantsMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $variant;
    }

    public function getArticleColumns()
    {
        return array(
            'article.id as articleId',
            'article.name as name',
            'article.active as active',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            'article.highlight as highlight',
            'article.metaTitle as metaTitle',
            'article.keywords as keywords',
            'article.priceGroupId as pricegroupId',
            'article.priceGroupActive as priceGroupActive',
            'article.lastStock as lastStock',
            'articleTax.tax as tax',
        );
    }

    public function getVariantColumns()
    {
        return array(
            'variants.id as variantId',
            'variants.number as orderNumber',
            'variants.kind as kind',
            'variants.additionalText as additionalText',
            'variants.inStock as inStock',
            'variants.stockMin as stockMin',
            'variants.shippingTime as shippingTime',
            'variants.shippingFree as shippingFree',
            'variants.supplierNumber as supplierNumber',
            'variants.minPurchase as minPurchase',
            'variants.purchaseSteps as purchaseSteps',
            'variants.maxPurchase as maxPurchase',
            'variants.purchaseUnit as purchaseUnit',
            'variants.referenceUnit as referenceUnit',
            'variants.packUnit as packUnit',
            'variants.unitId as unitId',
            'variants.weight as weight',
            'variants.width as width',
            'variants.height as height',
            'variants.len as length',
            'variants.ean as ean',
        );
    }
    
    public function getArticleMap()
    {
        if ($this->articleMap === null) {
            $columns = $this->getArticleColumns();

            foreach ($columns as $column) {

                $map = $this->generateMap($column);
                $this->articleMap[$map[0]] = $map[1];
            }
        }

        return $this->articleMap;
    }

    public function getVariantsMap()
    {
        if ($this->variantMap === null) {
            $columns = $this->getVariantColumns();

            foreach ($columns as $column) {

                $map = $this->generateMap($column);
                $this->variantMap[$map[0]] = $map[1];
            }
        }

        return $this->variantMap;
    }
    
    public function getTaxMap()
    {
        if ($this->taxMap === null) {
            $columns = $this->getTaxColumns();

            foreach ($columns as $column) {

                $map = $this->generateMap($column);
                $this->taxMap[$map[0]] = $map[1];
            }
        }

        return $this->taxMap;
    }

    public function generateMap($column)
    {
        preg_match('/(?<=as ).*/', $column, $alias);
        $alias = trim($alias[0]);

        preg_match("/(?<=\.).*?(?= as)/", $column, $name);
        $name = trim($name[0]);

        return array($alias, $name);
    }

    /**
     * Returns article repository
     * 
     * @return Shopware\Models\Article\Article
     */
    public function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->getManager()->getRepository('Shopware\Models\Article\Article');
        }

        return $this->repository;
    }

    /*
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
