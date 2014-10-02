<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;

class ArticlesPricesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $detailRepository;
    protected $priceRepository;
    protected $groupRepository;

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('price.id')
                ->from('Shopware\Models\Article\Article', 'article')
                ->leftJoin('article.details', 'detail')
                ->leftJoin('detail.prices', 'price')
                ->andWhere('price.price > 0')
                ->orderBy('price.id', 'ASC');

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

    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles_no_ids', 'Can not read articles without ids');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles_no_column_names', 'Can not read articles without column names.');
            throw new \Exception($message);
        }
        
        $columns = array_merge(
                $columns, array('customerGroup.taxInput as taxInput', 'articleTax.tax as tax')
        );

        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();
        $builder->select($columns)
                ->from('Shopware\Models\Article\Article', 'article')
                ->leftJoin('article.details', 'detail')
                ->leftJoin('article.tax', 'articleTax')
                ->leftJoin('article.supplier', 'supplier')
                ->leftJoin('detail.prices', 'price')
                ->leftJoin('price.customerGroup', 'customerGroup')
                ->where('price.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result['default'] = $builder->getQuery()->getResult();

        // add the tax if needed
        foreach ($result['default'] as &$record) {

            if ($record['taxInput']) {
                $record['price'] = str_replace('.',',',round($record['price'] * (100 + $record['tax']) / 100, 2));
                $record['pseudoPrice'] = str_replace('.',',',round($record['pseudoPrice'] * (100 + $record['tax']) / 100, 2));
            } else {
                $record['price'] = str_replace('.',',',round($record['price'], 2));
                $record['pseudoPrice'] = str_replace('.',',',  round($record['pseudoPrice'], 2));
            }

            if ($record['basePrice']) {
                $record['basePrice'] = str_replace('.',',',round($record['basePrice'], 2));
            }
        }
        
        return $result;
    }

    public function getDefaultColumns()
    {
        return array(
            'detail.number as orderNumber',
            'price.id',
            'price.articleId',
            'price.articleDetailsId',
            'price.from',
            'price.to',
            'price.price',
            'price.pseudoPrice',
            'price.basePrice',
            'price.percent',
            'price.customerGroupKey as priceGroup',
            'article.name as name',
            'detail.additionalText as additionalText',
            'supplier.name as supplierName',
//            'articleTax.id as taxId',
//            'articleTax.tax as tax',
        );
    }

    /**
     * Imports the records. <br/>
     * <b>Note:</b> The logic is copied from the old Import/Export Module
     * 
     * @param array $records
     */
    public function write($records)
    {
        $manager = $this->getManager();
        foreach ($records['default'] as $record) {

            // maybe this should be required field
            if (!isset($record['orderNumber']) || empty($record['orderNumber'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/ordernumber_required', 'Can not read articles without column names.');
                throw new \Exception($message);
            }

            if (empty($record['priceGroup'])) {
                $record['priceGroup'] = 'EK';
            }
            
            $customerGroup = $this->getGroupRepository()->findOneBy(array("key" => $record['priceGroup']));
            if (!$customerGroup) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articlesPrices/price_group_not_found', 'Price group %s was not found');
                throw new \Exception(sprintf($message, $record['priceGroup']));
            }
            
            $articleDetail = $this->getDetailRepository()->findOneBy(array("number" => $record['orderNumber']));
            if (!$articleDetail) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/article_number_not_found', 'Article with order number %s doen not exists');
                throw new \Exception(sprintf($message, $record['orderNumber']));
            }

            if (empty($record['from'])) {
                $record['from'] = 1;
            } else {
                $record['from'] = intval($record['from']);
            }

//            $oldPrice = $this->getPriceRepository()->findOneBy(
//                array(
//                    'articleDetailsId' => $articleDetail->getId(),
//                    'customerGroupKey' => $record['priceGroup'],
//                    'from' => $record['from']
//                )
//            );

            $tax = $articleDetail->getArticle()->getTax();

            if (empty($record['price']) && empty($record['percent'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articlesPrices/price_percent_val_missing', 'Price or percent value is missing');
                throw new \Exception($message);
            }

            if ($record['from'] <= 1 && empty($record['price'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articlesPrices/price_val_missing', 'Price value is missing');
                throw new \Exception($message);
            }

            if (isset($record['price'])) {
                $record['price'] = floatval(str_replace(",", ".", $record['price']));                
            }

            if (isset($record['pseudoPrice'])) {
                $record['pseudoPrice'] = floatval(str_replace(",", ".", $record['pseudoPrice']));
            } else {
//                if ($oldPrice) {
//                    $record['pseudoPrice'] = $oldPrice->getPseudoPrice();
//                } else {
//                    $record['pseudoPrice'] = 0;
//                }
//
//                if ($customerGroup->getTaxInput()) {
//                    $record['pseudoPrice'] = round($record['pseudoPrice'] * (100 + $tax->getTax()) / 100, 2);
//                }
            }

            if (isset($record['basePrice'])) {
                $record['basePrice'] = floatval(str_replace(",", ".", $record['basePrice']));
            } else {
//                if ($oldPrice) {
//                    $record['basePrice'] = $oldPrice->getBasePrice();
//                }
            }

            if (isset($record['percent'])) {
                $record['percent'] = floatval(str_replace(",", ".", $record['percent']));
            } else {
//                if ($oldPrice) {
//                    $record['percent'] = $oldPrice->getPercent();
//                }
            }

            $query = $manager->createQuery('
                        DELETE FROM Shopware\Models\Article\Price price
                        WHERE price.customerGroup = :customerGroup
                        AND price.articleDetailsId = :detailId
                        AND price.from = :from');

            $query->setParameters(array(
                'customerGroup' => $record['priceGroup'],
                'detailId' => $articleDetail->getId(),
                'from' => $record['from'],
            ));
            $query->execute();

            if ($record['from'] != 1) {
                $query = $manager->createQuery('
                        UPDATE Shopware\Models\Article\Price price SET price.to = :to
                        WHERE price.customerGroup = :customerGroup
                        AND price.articleDetailsId = :detailId
                        AND price.articleId = :articleId AND price.to
                        LIKE \'beliebig\'');

                $query->setParameters(array(
                    'to' => $record['from'] - 1,
                    'customerGroup' => $record['priceGroup'],
                    'detailId' => $articleDetail->getId(),
                    'articleId' => $articleDetail->getArticle()->getId(),
                ));
                $query->execute();
            }

            // remove tax
            if ($customerGroup->getTaxInput()) {
                $record['price'] = $record['price'] / (100 + $tax->getTax()) * 100;
                $record['pseudoPrice'] = $record['pseudoPrice'] / (100 + $tax->getTax()) * 100;
            }

            $price = new \Shopware\Models\Article\Price();
            $price->setArticle($articleDetail->getArticle());
            $price->setDetail($articleDetail);
            $price->setCustomerGroup($customerGroup);
            $price->setFrom($record['from']);
            $price->setTo('beliebig');
            $price->setPrice($record['price']);
            if (isset($record['pseudoPrice'])) {
                $price->setPseudoPrice($record['pseudoPrice']);
            }
            $price->setBasePrice($record['basePrice']);
            $price->setPercent($record['percent']);


            $this->getManager()->persist($price);
        }
        $this->getManager()->flush();
        $this->getManager()->clear();
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
    public function getDetailRepository()
    {
        if ($this->detailRepository === null) {
            $this->detailRepository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->detailRepository;
    }

    /**
     * Returns article detail repository
     * 
     * @return Shopware\Models\Customer\Group
     */
    public function getGroupRepository()
    {
        if ($this->groupRepository === null) {
            $this->groupRepository = $this->getManager()->getRepository('Shopware\Models\Customer\Group');
        }

        return $this->groupRepository;
    }
    
    /**
     * Returns price repository
     *
     * @return Shopware\Models\Article\Price
     */
    public function getPriceRepository()
    {
        if ($this->priceRepository === null) {
            $this->priceRepository = $this->getManager()->getRepository('Shopware\Models\Article\Price');
        }

        return $this->priceRepository;
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
