<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class ArticlesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

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
        return array(
            'article.id as articleId',
            'variants.id as variantId',
            'article.name as name',
            'variants.additionalText as additionalText',
            'supplier.name as supplierName',
            'articleTax.tax as tax',
            'prices.price as netPrice',
            'prices.pseudoPrice as pseudoPrice',
            'prices.basePrice as basePrice',
            'article.active as active',
            'variants.inStock as inStock',
            'variants.stockMin as stockMin',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            'variants.shippingTime as shippingTime',
            'variants.shippingFree as shippingFree',
            'article.highlight as highlight',
            'article.metaTitle as metaTitle',
            'article.keywords as keywords',
            'variants.minPurchase as minPurchase',
            'variants.purchaseSteps as purchaseSteps',
            'variants.maxPurchase as maxPurchase',
            'variants.purchaseUnit as purchaseUnit',
            'variants.referenceUnit as referenceUnit',
            'variants.packUnit as packUnit',
            'variants.unitId as unitId',
            'article.priceGroupId as pricegroupId',
            'article.priceGroupActive as priceGroupActive',
            'article.lastStock as lastStock',
            'variants.supplierNumber as supplierNumber',
            'articleEsd.file as esd',
            'variants.weight as weight',
            'variants.width as width',
            'variants.height as height',
            'variants.len as length',
            'variants.ean as ean',
            'variantsUnit.unit as unit',
        );
    }

    public function write($records)
    {
        
    }

    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

}
