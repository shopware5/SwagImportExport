<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Article\Article as ArticleModel;
use Shopware\Models\Article\Detail as DetailModel;
use Shopware\Models\Article\Price as Price;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Components\SwagImportExport\Utils\DataHelper as DataHelper;

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
    protected $variantRepository;
    protected $groupRepository;
    
    //mappers
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
        $articlesBuilder = $manager->createQueryBuilder();
        $articlesBuilder->select($columns['article'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->join('variant.article', 'article')
                ->leftJoin('Shopware\Models\Article\Detail', 'mv', \Doctrine\ORM\Query\Expr\Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
                ->leftJoin('article.tax', 'articleTax')
                ->leftJoin('article.supplier', 'supplier')
                ->leftJoin('article.esds', 'articleEsd')
                ->leftJoin('variant.unit', 'variantsUnit')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result['articles'] = $articlesBuilder->getQuery()->getResult();

        $pricesBuilder = $manager->createQueryBuilder();
        $pricesBuilder->select($columns['price'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->leftJoin('variant.prices', 'prices')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', $ids);
        $result['prices'] = $pricesBuilder->getQuery()->getResult();

        return $result;
    }

    public function getDefaultColumns()
    {
        $otherColumns = array(
            'variantsUnit.unit as unit',
            'articleEsd.file as esd',
            'supplier.name as supplierName',
        );

        $columns['article'] = array_merge(
                $this->getArticleColumns(), $this->getVariantColumns(), $otherColumns
        );

        $columns['price'] = $this->getPriceColumns();

        return $columns;
    }

    public function write($records)
    {
        //articles
        if (empty($records['articles'])) {
            throw new \Exception('No article records were found.');
        }

        foreach ($records['articles'] as $index => $record) {
            $article = null;
            $variant = null;

            if (isset($record['articleId']) && !empty($record['articleId'])) {
                $article = $this->getManager()->find('Shopware\Models\Article\Article', $record['articleId']);
                unset($record['articleId']);
            } elseif (!$this->isMainVariant($record)) {
                $mainVariant = $this->getVariantRepository()->findOneBy(array('number' => $record['mainNumber']));

                if (!$mainVariant) {
                    throw new Exception('Variant does not exists');
                }
                $article = $mainVariant->getArticle();

                unset($record['mainNumber']);
            }

            if (!$article) {
                //if the article does not exists
                $article = new ArticleModel();

                $articleData = $this->prerpareArticle($record);

                $variant = $this->prerpareVariant($record, $article);

                $article->setDetails($variant);

                $prices = $this->preparePrices($records['prices'], $index, $variant, $article, $articleData['tax']);

                $articleData['mainDetail'] = array(
                    'number' => $variant->getNumber(),
                    'prices' => $prices
                );
                $article->fromArray($articleData);

                $violations = $this->getManager()->validate($article);

                if ($violations->count() > 0) {
                    throw new \Exception('No valid entity');
                }

                $this->getManager()->persist($article);
            } else {

                //Variants
                $variant = $this->prerpareVariant($record, $article);
                $variant->setArticle($article);

                $prices = $this->preparePrices($records['prices'], $index, $variant, $article, $article->getTax());

                $variant->setPrices($prices);

                $violations = $this->getManager()->validate($variant);

                if ($violations->count() > 0) {
                    throw new \Exception('No valid entity');
                }

                $this->getManager()->persist($variant);
            }

            $this->getManager()->flush();
        }
    }

    public function prerpareArticle(&$data)
    {
        $article = array();

        //check if a tax id is passed and load the tax model or set the tax parameter to null.
        if (!empty($data['taxId'])) {
            $article['tax'] = $this->getManager()->find('Shopware\Models\Tax\Tax', $data['taxId']);

            if (empty($data['tax'])) {
                throw new \Exception(sprintf("Tax by id %s not found", $data['taxId']));
            }
        } elseif (!empty($data['tax'])) {
            $tax = $this->getManager()->getRepository('Shopware\Models\Tax\Tax')->findOneBy(array('tax' => $data['tax']));
            if (!$tax) {
                throw new \Exception(sprintf("Tax by taxrate %s not found", $data['tax']));
            }
            $article['tax'] = $tax;
        }
        unset($data['tax']);

        //check if a supplier id is passed and load the supplier model or set the supplier parameter to null.
        if (!empty($data['supplierId'])) {
            $article['supplier'] = $this->getManager()->find('Shopware\Models\Article\Supplier', $data['supplierId']);
            if (empty($article['supplier'])) {
                throw new \Exception(sprintf("Supplier by id %s not found", $data['supplierId']));
            }
        } elseif (!empty($data['supplierName'])) {
            $supplier = $this->getManager()->getRepository('Shopware\Models\Article\Supplier')->findOneBy(array('name' => $data['supplierName']));
            if (!$supplier) {
                $supplier = new \Shopware\Models\Article\Supplier();
                $supplier->setName($article['supplierName']);
            }
            $article['supplier'] = $supplier;
        }
        unset($data['supplierName']);

        $articleMap = $this->getMap('article');

        foreach ($data as $key => $value) {
            if (isset($articleMap[$key])) {
                $article[$articleMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $article;
    }

    public function prerpareVariant(&$data, $article)
    {
        $variantData = array();

        if (isset($data['variantId']) && !empty($data['variantId'])) {
            $variant = $this->getManager()->find('Shopware\Models\Article\Detail', $data['variantId']);
            unset($data['variantId']);
        }

        if (!$variant) {
            $variant = new DetailModel();
            $variant->setArticle($article);
        }

        $variantsMap = $this->getMap('variant');

        foreach ($data as $key => $value) {
            if (isset($variantsMap[$key])) {
                $variantData[$variantsMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        $variant->fromArray($variantData);

        return $variant;
    }

    public function preparePrices(&$data, $variantIndex, $variant, $article, $tax)
    {
        $prices = array();

        foreach ($data as $index => $priceData) {
            if ($priceData['parentIndexElement'] === $variantIndex) {
                $price = new Price();

                if (empty($priceData['priceGroup'])) {
                    $priceData['priceGroup'] = 'EK';
                }

                // load the customer group of the price definition
                $customerGroup = $this->getGroupRepository()->findOneBy(array('key' => $priceData['priceGroup']));

                /** @var CustomerGroup $customerGroup */
                if (!$customerGroup instanceof CustomerGroup) {
                    throw new \Exception(sprintf('Customer Group by key %s not found', $priceData['priceGroup']));
                }

                if (!isset($priceData['from'])) {
                    $priceData['from'] = 1;
                }

                $priceData['from'] = intval($priceData['from']);

                $priceData['to'] = intval($priceData['to']);

                // if the "to" value isn't numeric, set the place holder "beliebig"
                if ($priceData['to'] <= 0) {
                    $priceData['to'] = 'beliebig';
                }

                if ($priceData['from'] <= 0) {
                    throw new \Exception(sprintf('Invalid Price "from" value'));
                }

                $priceData['price'] = floatval(str_replace(",", ".", $priceData['price']));
                $priceData['basePrice'] = floatval(str_replace(",", ".", $priceData['basePrice']));
                $priceData['pseudoPrice'] = floatval(str_replace(",", ".", $priceData['pseudoPrice']));
                $priceData['percent'] = floatval(str_replace(",", ".", $priceData['percent']));

                if ($customerGroup->getTaxInput()) {
                    $priceData['price'] = $priceData['price'] / (100 + $tax->getTax()) * 100;
                    $priceData['pseudoPrice'] = $priceData['pseudoPrice'] / (100 + $tax->getTax()) * 100;
                }

                $priceData['customerGroup'] = $customerGroup;
                $priceData['article'] = $article;
                $priceData['detail'] = $variant;

                $price->fromArray($priceData);
                $prices[] = $price;

                unset($data[$index]);
            } else {
                break;
            }
        }

        return $prices;
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
            'article.priceGroupId as priceGroupId',
            'article.priceGroupActive as priceGroupActive',
            'article.lastStock as lastStock',
            'articleTax.tax as tax',
        );
    }

    public function getVariantColumns()
    {
        return array(
            'variant.id as variantId',
            'variant.number as orderNumber',
            'mv.number as mainNumber',
            'variant.kind as kind',
            'variant.additionalText as additionalText',
            'variant.inStock as inStock',
            'variant.stockMin as stockMin',
            'variant.shippingTime as shippingTime',
            'variant.shippingFree as shippingFree',
            'variant.supplierNumber as supplierNumber',
            'variant.minPurchase as minPurchase',
            'variant.purchaseSteps as purchaseSteps',
            'variant.maxPurchase as maxPurchase',
            'variant.purchaseUnit as purchaseUnit',
            'variant.referenceUnit as referenceUnit',
            'variant.packUnit as packUnit',
            'variant.unitId as unitId',
            'variant.weight as weight',
            'variant.width as width',
            'variant.height as height',
            'variant.len as length',
            'variant.ean as ean',
        );
    }

    public function getPriceColumns()
    {
        return array(
            'prices.articleDetailsId as variantId',
            'prices.articleId as articleId',
            'prices.price as price',
            'prices.pseudoPrice as pseudoPrice',
            'prices.basePrice as basePrice',
            'prices.customerGroupKey as priceGroup',
        );
    }

    /**
     * Returns/Creates mapper depend on the key
     * Exmaple: articles, variants, prices ...
     * 
     * @param string $key
     * @return array
     */
    public function getMap($key)
    {
        $property = $key . 'Map';
        if ($this->{$property} === null) {
            $method = 'get' . ucfirst($key) . 'Columns';
            if (method_exists($this, $method)) {
                $columns = $this->{$method}();

                foreach ($columns as $column) {
                    $map = DataHelper::generateMappingFromColumns($column);
                    $this->{$property}[$map[0]] = $map[1];
                }
            }
        }

        return $this->{$property};
    }

    public function isMainVariant($data)
    {
        if ($data['orderNumber'] === $data['mainNumber']) {
            return true;
        }

        return false;
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

    /**
     * Returns deatil repository
     * 
     * @return Shopware\Models\Article\Detail
     */
    public function getVariantRepository()
    {
        if ($this->variantRepository === null) {
            $this->variantRepository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->variantRepository;
    }

    /**
     * Returns group repository
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
