<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Models\Article\Article as ArticleModel;
use Shopware\Models\Article\Detail as DetailModel;
use Shopware\Models\Article\Price as Price;
use Shopware\Models\Article\Image as Image;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Models\Media\Media as MediaModel;
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
        
        if ($filter['variants']) {
            $builder->where('detail.kind <> 3');
        } else {
            $builder->where('detail.kind = 1');
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
                ->leftJoin('variant.attribute', 'attr')
                ->leftJoin('Shopware\Models\Article\Detail', 'mv', \Doctrine\ORM\Query\Expr\Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
                ->leftJoin('article.tax', 'articleTax')
                ->leftJoin('article.supplier', 'supplier')
                ->leftJoin('article.propertyGroup', 'filterGroup')
                ->leftJoin('article.esds', 'articleEsd')
                ->leftJoin('variant.unit', 'variantsUnit')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', $ids);

        $result['articles'] = $articlesBuilder->getQuery()->getResult();
        
        //prices
        $pricesBuilder = $manager->createQueryBuilder();
        $pricesBuilder->select($columns['price'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->leftJoin('variant.prices', 'prices')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', $ids);
        $result['prices'] = $pricesBuilder->getQuery()->getResult();
        
        //images
        $imagesBuilder = $manager->createQueryBuilder();
        $imagesBuilder->select($columns['image'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->join('variant.article', 'article')
                ->leftjoin('article.images', 'images')
                ->where('variant.id IN (:ids)')
                ->andWhere('variant.kind = 1')
                ->andWhere('images.id IS NOT NULL')
                ->setParameter('ids', $ids);
        $result['images'] = $imagesBuilder->getQuery()->getResult();
        
        //filter values
        $propertyValuesBuilder = $manager->createQueryBuilder();
        $propertyValuesBuilder->select($columns['propertyValues'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->join('variant.article', 'article')
                ->leftjoin('article.propertyValues', 'propertyValues')
                ->where('variant.id IN (:ids)')
                ->andWhere('propertyValues.id IS NOT NULL')
                ->setParameter('ids', $ids);
        $result['propertyValues'] = $propertyValuesBuilder->getQuery()->getResult();
        
        //similar 
        $similarsBuilder = $manager->createQueryBuilder();
        $similarsBuilder->select($columns['similar'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->join('variant.article', 'article')
                ->leftjoin('article.similar', 'similar')
                ->where('variant.id IN (:ids)')
                ->andWhere('variant.kind = 1')
                ->andWhere('similar.id IS NOT NULL')
                ->setParameter('ids', $ids);
        $result['similars'] = $similarsBuilder->getQuery()->getResult();
        
        return $result;
    }

    public function getDefaultColumns()
    {
        $otherColumns = array(
            'variantsUnit.unit as unit',
            'articleEsd.file as esd',
        );

        $columns['article'] = array_merge(
                $this->getArticleColumns(), $this->getVariantColumns(), $otherColumns
        );

        $columns['price'] = $this->getPriceColumns();
        $columns['image'] = $this->getImageColumns();
        $columns['propertyValues'] = $this->getPropertyValueColumns();
        $columns['similar'] = $this->getSimilarColumns();

        return $columns;
    }

    public function write($records)
    {
        //articles
        if (empty($records['articles'])) {
            throw new \Exception('No article records were found.');
        }
        
        foreach ($records['articles'] as $index => $record) {
            $articleModel = null;
            $variantModel = null;
            
            if (!isset($record['orderNumber']) && empty($record['orderNumber'])) {
                throw new \Exception('Order number is required.');
            } 
            
            $variantModel = $this->getVariantRepository()->findOneBy(array('number' => $record['orderNumber']));

            if ($variantModel) {
                $articleModel = $variantModel->getArticle();                    
            } else if ($record['mainNumber'] !== $record['orderNumber']) {
                $mainVariant = $this->getVariantRepository()->findOneBy(array('number' => $record['mainNumber']));
                
                if (!$mainVariant) {
                    throw new \Exception(sprintf('Variant with number %s does not exists', $record['mainNumber']));
                }
                $articleModel = $mainVariant->getArticle();
                unset($record['mainNumber']);
            }
            
            if (!$articleModel) {
                //creates artitcle and main variant
                $articleModel = new ArticleModel();

                $articleData = $this->prerpareArticle($record);
                $variantModel = $this->prerpareVariant($record, $articleModel);
                $articleModel->setDetails($variantModel);

                $prices = $this->preparePrices($records['prices'], $index, $variantModel, $articleModel, $articleData['tax']);
                $articleData['images'] = $this->prepareImages($records['images'], $index, $articleModel);
                $articleData['similar'] = $this->prepareSimilars($records['similars'], $index, $articleModel);

                $articleData['mainDetail'] = array(
                    'number' => $variantModel->getNumber(),
                    'prices' => $prices
                );
                $articleModel->fromArray($articleData);
                
                $violations = $this->getManager()->validate($articleModel);

                if ($violations->count() > 0) {
                    throw new \Exception('No valid entity');
                }

                $this->getManager()->persist($articleModel);
            } else {
                //if it is main variant 
                //updates the also the article
                if ($record['mainNumber'] === $record['orderNumber']) {
                    $articleData = $this->prerpareArticle($record);
                    $articleData['images'] = $this->prepareImages($records['images'], $index, $articleModel);
                    $articleData['similar'] = $this->prepareSimilars($records['similars'], $index, $articleModel);
                    
                    $articleModel->fromArray($articleData);
                }
                
                //Variants
                $variantModel = $this->prerpareVariant($record, $articleModel, $variantModel);
                $variantModel->setArticle($articleModel);

                $prices = $this->preparePrices($records['prices'], $index, $variantModel, $articleModel, $articleModel->getTax());

                $variantModel->setPrices($prices);

                $violations = $this->getManager()->validate($variantModel);

                if ($violations->count() > 0) {
                    throw new \Exception('No valid entity');
                }
                
                $this->getManager()->persist($variantModel);
            }
            
            $this->getManager()->flush();
            $this->getManager()->clear($articleModel);
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
        
        //check if a priceGroup id is passed and load the priceGroup model or set the priceGroup parameter to null.
        if (isset($data['priceGroupId'])) {
            if (empty($data['priceGroupId'])) {
                $article['priceGroupId'] = null;
            } else {
                $article['priceGroup'] = $this->getManager()->find('Shopware\Models\Price\Group', $data['priceGroupId']);
                if (empty($article['priceGroup'])) {
                    throw new \Exception(sprintf("Pricegroup by id %s not found", $data['priceGroupId']));
                }
            }
            unset($data['priceGroup']);
        }

        //check if a propertyGroup is passed and load the propertyGroup model or set the propertyGroup parameter to null.
        if (isset($data['propertyGroupId'])) {
            if (empty($data['propertyGroupId'])) {
                $article['propertyGroup'] = null;
            } else {
                $article['propertyGroup'] = $this->getManager()->find('\Shopware\Models\Property\Group', $data['filterGroupId']);

                if (empty($article['propertyGroup'])) {
                    throw new \Exception(sprintf("PropertyGroup by id %s not found", $data['filterGroupId']));
                }
            }
            unset($data['propertyGroupId']);
        } 

        $articleMap = $this->getMap('article');

        foreach ($data as $key => $value) {
            if (isset($articleMap[$key])) {
                $article[$articleMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $article;
    }

    public function prerpareVariant(&$data, ArticleModel $article, $variantModel = null)
    {
        $variantData = array();

        if (!$variantModel) {
            $variantModel = new DetailModel();
            $variantModel->setArticle($article);
        }

        $variantsMap = $this->getMap('variant');

        foreach ($data as $key => $value) {
            if (isset($variantsMap[$key])) {
                $variantData[$variantsMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        $variantModel->fromArray($variantData);

        return $variantModel;
    }

    public function preparePrices(&$data, $variantIndex, $variant, ArticleModel $article, $tax)
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
            } 
        }

        return $prices;
    }
    
    public function prepareImages(&$data, $variantIndex, ArticleModel $article)
    {
        foreach ($data as $key => $imageData) {
            
            if ($imageData['parentIndexElement'] === $variantIndex) {
                
                if (isset($imageData['id'])) {
                    $imageModel = $this->getManager()->find(
                            'Shopware\Models\Article\Image', (int) $imageData['id']
                    );
                    unset($imageData['id']);
                } elseif ($article->getImages() && $imageData['path']) {
                    foreach ($article->getImages() as $articleImage) {
                        if ($imageData['path'] == $articleImage->getPath()) {
                            $imageModel = $articleImage;
                            break;
                        }
                    }
                }
                
                if (!$imageModel) {
                    
                    if (!empty($imageData['mediaId'])) {
                        $media = $this->getManager()->find(
                                'Shopware\Models\Media\Media', (int) $imageData['mediaId']
                        );
                    }

                    if (!($media instanceof MediaModel)) {
                        throw new \Exception(sprintf("Media by mediaId %s not found", $imageData['mediaId']));
                    }
                    
                    $imageModel = $this->createNewArticleImage($article, $media);
                }
                $imageModel->fromArray($imageData);
                
                $images[] = $imageModel;
                unset($data[$key]);
            }
        }
        
        $hasMain = $this->getCollectionElementByProperty($images, 'main', 1);
        
        if (!$hasMain) {
            $image = $images->get(0);
            $image->setMain(1);
        }
        
        return $images;        
    }
    
    /**
     * Helper function which creates a new article image with the passed media object.
     * @param ArticleModel $article
     * @param MediaModel $media
     * @return Image
     */
    public function createNewArticleImage(ArticleModel $article, MediaModel $media)
    {
        $image = new Image();
        $image = $this->updateArticleImageWithMedia(
            $article,
            $image,
            $media
        );
        $this->getManager()->persist($image);
        $article->getImages()->add($image);
        return $image;
    }

    /**
     * Helper function to map the media data into an article image
     *
     * @param ArticleModel $article
     * @param Image $image
     * @param MediaModel $media
     * @return Image
     */
    public function updateArticleImageWithMedia(ArticleModel $article, Image $image, MediaModel $media)
    {
        $image->setMain(2);
        $image->setMedia($media);
        $image->setArticle($article);
        $image->setPath($media->getName());
        $image->setExtension($media->getExtension());
        $image->setDescription($media->getDescription());

        return $image;
    }
    
    public function prepareSimilars(&$similars, $similarIndex, $article)
    {
        $similarCollection = array();

        foreach ($similars as $index => $similar) {
            if ($similar['parentIndexElement'] != $similarIndex) {
                continue;
            }

            if (!$similar['similarId']) {
                continue;
            }

            if ($this->isSimilarArticleExists($article, $similar['similarId'])) {
                continue;
            }

            $similarModel = $this->getManager()->getReference('Shopware\Models\Article\Article', $similar['similarId']);

            $similarCollection[] = $similarModel;

            unset($similars[$index]);
        }

        return $similarCollection;
    }

    public function isSimilarArticleExists($article, $similarId)
    {
        foreach ($article->getSimilar() as $similar){
            if ($similar->getId == $similarId) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * @param ArrayCollection $collection
     * @param $property
     * @param $value
     * @throws \Exception
     * @return null
     */
    protected function getCollectionElementByProperty(ArrayCollection $collection, $property, $value)
    {
        foreach ($collection as $entity) {
            $method = 'get' . ucfirst($property);

            if (!method_exists($entity, $method)) {
                throw new \Exception(
                    sprintf("Method %s not found on entity %s", $method, get_class($entity))
                );
                continue;
            }
            if ($entity->$method() == $value) {
                return $entity;
            }
        }
        return null;
    }

    public function getArticleColumns()
    {
        $columns = array(
            'article.id as articleId',
            'article.name as name',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            'article.added as date',
            'article.active as active',
            'article.pseudoSales as pseudoSales',
            'article.highlight as topSeller',
            'article.metaTitle as metaTitle',
            'article.keywords as keywords',
            'article.changed as changeTime',
            'article.priceGroupId as priceGroupId',
            'article.priceGroupActive as priceGroupActive',
            'article.lastStock as lastStock',
            'article.crossBundleLook as crossBundleLook',
            'article.notification as notification',
            'article.template as template',
            'article.mode as mode',
            'article.availableFrom as availableFrom',
            'article.availableTo as availableTo',
            'supplier.id as supplierId',
            'supplier.name as supplierName',
            'articleTax.id as taxId',
            'articleTax.tax as tax',
            'filterGroup.id as filterGroupId',
            'filterGroup.name as filterGroupName',
        );

        // Attributes
        $stmt = Shopware()->Db()->query('SELECT * FROM s_articles_attributes LIMIT 1');
        $attributes = $stmt->fetch();

        $attributesSelect = '';
        if ($attributes) {
            unset($attributes['id']);
            unset($attributes['articleID']);
            unset($attributes['articledetailsID']);
            $attributes = array_keys($attributes);

            $prefix = 'attr';
            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase
                $catAttr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $catAttr, ucwords($catAttr));
            }
        }
        
        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }
        
        return $columns;
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
            'variant.weight as weight',
            'variant.position as position',
            'variant.width as width',
            'variant.height as height',
            'variant.len as length',
            'variant.ean as ean',
            'variant.unitId as unitId',
            'variant.purchaseSteps as purchaseSteps',
            'variant.minPurchase as minPurchase',
            'variant.maxPurchase as maxPurchase',
            'variant.purchaseUnit as purchaseUnit',
            'variant.referenceUnit as referenceUnit',
            'variant.packUnit as packUnit',
            'variant.packUnit as releaseDate',
            'variant.shippingTime as shippingTime',
            'variant.shippingFree as shippingFree',
            'variant.supplierNumber as supplierNumber',
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
    
    public function getImageColumns()
    {
        return array(
            'images.id as id',
            'images.articleId as articleId',
            'images.articleDetailId as variantId',
            'images.path as path',
            'images.main as main',
            'images.mediaId as mediaId',
        );
    }
    
    public function getPropertyValueColumns()
    {
        return array(
            'propertyValues.id as propertyGroupId',
            'article.id as articleId',
            'propertyValues.value as value',
            'propertyValues.position as position',
            'propertyValues.optionId as optionId',
            'propertyValues.valueNumeric as valueNumeric',
        );
    }
    
    public function getSimilarColumns()
    {
         return array(
            'similar.id as similarId',
            'article.id as articleId',
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
