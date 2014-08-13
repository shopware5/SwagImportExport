<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Models\Article\Article as ArticleModel;
use Shopware\Models\Article\Detail as DetailModel;
use Shopware\Models\Article\Price as Price;
use Shopware\Models\Article\Image as Image;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Models\Article\Configurator;
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
    protected $articleVariantMap;
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
                ->setParameter('ids', $ids)
                ->orderBy("variant.kind");

        $result['article'] = $articlesBuilder->getQuery()->getResult();
        
        //prices
        $pricesBuilder = $manager->createQueryBuilder();
        $pricesBuilder->select($columns['price'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->leftJoin('variant.prices', 'prices')
                ->where('variant.id IN (:ids)')
                ->setParameter('ids', $ids);
        $result['price'] = $pricesBuilder->getQuery()->getResult();
        
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
        $result['image'] = $imagesBuilder->getQuery()->getResult();
        
        //filter values
        $propertyValuesBuilder = $manager->createQueryBuilder();
        $propertyValuesBuilder->select($columns['propertyValues'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->join('variant.article', 'article')
                ->leftjoin('article.propertyValues', 'propertyValues')
                ->where('variant.id IN (:ids)')
                ->andWhere('variant.kind = 1')
                ->andWhere('propertyValues.id IS NOT NULL')
                ->setParameter('ids', $ids);
        $result['propertyValue'] = $propertyValuesBuilder->getQuery()->getResult();
        
        //configurator
        $configBuilder = $manager->createQueryBuilder();
        $configBuilder->select($columns['configurator'])
                ->from('Shopware\Models\Article\Detail', 'variant')
                ->join('variant.article', 'article')
                ->leftjoin('variant.configuratorOptions', 'configuratorOptions')
                ->leftjoin('configuratorOptions.group', 'configuratorGroup')
                ->leftjoin('article.configuratorSet', 'configuratorSet')
                ->where('variant.id IN (:ids)')
                ->andWhere('configuratorOptions.id IS NOT NULL')
                ->andWhere('configuratorGroup.id IS NOT NULL')
                ->andWhere('configuratorSet.id IS NOT NULL')
                ->setParameter('ids', $ids);
        $result['configurator'] = $configBuilder->getQuery()->getResult();
        
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
        $result['similar'] = $similarsBuilder->getQuery()->getResult();
        
        return $result;
    }

    /**
     * Returns default columns
     * @return array
     */
    public function getDefaultColumns()
    {
        $otherColumns = array(
            'variantsUnit.unit as unit',
            'articleEsd.file as esd',
        );

        $columns['article'] = array_merge(
                $this->getArticleColumns(), $otherColumns
        );

        $columns['price'] = $this->getPriceColumns();
        $columns['image'] = $this->getImageColumns();
        $columns['propertyValues'] = $this->getPropertyValueColumns();
        $columns['similar'] = $this->getSimilarColumns();
        $columns['configurator'] = $this->getConfiguratorColumns();
        
        return $columns;
    }

    public function write($records)
    {
        //articles
        if (empty($records['article'])) {
            throw new \Exception('No article records were found.');
        }
        
        foreach ($records['article'] as $index => $record) {
            
            if (!isset($record['orderNumber']) && empty($record['orderNumber'])) {
                throw new \Exception('Order number is required.');
            } 
            
            if (!isset($record['mainNumber']) && empty($record['mainNumber'])) {
                throw new \Exception('Main order number is required.');
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
//                unset($record['mainNumber']);
            }

            if (!$articleModel) {
                //creates artitcle and main variant
                $articleModel = new ArticleModel();

                $articleData = $this->prerpareArticle($record);
                
                $mainDetailData = $this->prepareMainDetail($record, $articleData);
                
                $articleModel->setMainDetail($mainDetailData);
            
                $variantModel = $articleModel->getMainDetail();
                
                $prices = $this->preparePrices($records['price'], $index, $variantModel, $articleModel, $articleData['tax']);
                $variantModel->setPrices($prices);
                
                $articleData['images'] = $this->prepareImages($records['image'], $index, $articleModel);
                $articleData['similar'] = $this->prepareSimilars($records['similar'], $index, $articleModel);
                $articleData['configuratorSet'] = $this->prepareArticleConfigurators($records['configurator'], $index, $articleModel);
                
                $articleModel->fromArray($articleData);
                
                $violations = $this->getManager()->validate($articleModel);

                if ($violations->count() > 0) {
                    throw new \Exception('No valid article entity');
                }
                
                $this->getManager()->persist($articleModel);
            } else {
                //if it is main variant 
                //updates the also the article
                if ($record['mainNumber'] === $record['orderNumber']) {
                    $articleData = $this->prerpareArticle($record);
//                    $articleData['images'] = $this->prepareImages($records['image'], $index, $articleModel);
                    $articleData['similar'] = $this->prepareSimilars($records['similar'], $index, $articleModel);
                    $articleModel->fromArray($articleData);
                }
                
                //Variants
                $variantModel = $this->prerpareVariant($record, $articleModel, $variantModel);
            
                $configuratorOptions = $this->prepareVariantConfigurators($records['configurator'], $index, $articleModel);
                $variantModel->setConfiguratorOptions($configuratorOptions);
                
                $prices = $this->preparePrices($records['price'], $index, $variantModel, $articleModel, $articleModel->getTax());

                $variantModel->setPrices($prices);

                $violations = $this->getManager()->validate($variantModel);

                if ($violations->count() > 0) {
                    throw new \Exception('No valid detail entity');
                }
            
                $this->getManager()->persist($variantModel);
            }
            
            $this->getManager()->flush();
            
            unset($articleModel);
            unset($variantModel);
        }
        
    }
    
    public function getSections()
    {
        return array(
            array('id' => 'article', 'name' => 'article'),
            array('id' => 'price', 'name' => 'price'),
            array('id' => 'image', 'name' => 'image'),
            array('id' => 'propertyValue', 'name' => 'propertyValue'),
            array('id' => 'similar', 'name' => 'similar'),
            array('id' => 'configurator', 'name' => 'configurator'),
        );
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

        $articleMap = $this->getMap('articleVariant');
        
        foreach ($data as $key => $value) {
            if (isset($articleMap[$key])) {
                $article[$articleMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $article;
    }
    
    public function prepareMainDetail($data, $article)
    {
        $variantData = array();
        $variantsMap = $this->getMap('variant');

        foreach ($data as $key => $value) {
            if (isset($variantsMap[$key])) {
                $variantData[$variantsMap[$key]] = $value;
            }
        }
        
        $variantData['article'] = $article;
                
        return $variantData;
    }

    public function prerpareVariant(&$data, ArticleModel $article, $variantModel = null)
    {
        $variantData = array();

        if (!$variantModel) {
            $variantModel = new DetailModel();
            $variantModel->setArticle($article);
        }

        $variantsMap = $this->getMap('variant');
        
        unset($data['mainNumber']);
        foreach ($data as $key => $value) {
            if (isset($variantsMap[$key])) {
                $variantData[$variantsMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        $variantModel->fromArray($variantData);
        $this->getManager()->persist($variantModel);

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

                if (isset($priceData['to'])) {
                    $priceData['to'] = intval($priceData['to']);
                } else {
                    $priceData['to'] = 0;
                }

                // if the "to" value isn't numeric, set the place holder "beliebig"
                if ($priceData['to'] <= 0) {
                    $priceData['to'] = 'beliebig';
                }

                if ($priceData['from'] <= 0) {
                    throw new \Exception(sprintf('Invalid Price "from" value'));
                }

                $priceData['price'] = floatval(str_replace(",", ".", $priceData['price']));
                if (isset($priceData['basePrice'])) {
                    $priceData['basePrice'] = floatval(str_replace(",", ".", $priceData['basePrice']));
                } else {
                    $priceData['basePrice'] = 0.0;
                }
                if (isset($priceData['pseudoPrice'])) {
                    $priceData['pseudoPrice'] = floatval(str_replace(",", ".", $priceData['pseudoPrice']));
                } else {
                    $priceData['pseudoPrice'] = 0.0;
                }
                if (isset($priceData['percent'])) {
                    $priceData['percent'] = floatval(str_replace(",", ".", $priceData['percent']));
                } else {
                    $priceData['percent'] = 0.0;
                }

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
        if ($data == null) {
            return;
        }
        
        foreach ($data as $key => $imageData) {
            
            if ($imageData['parentIndexElement'] === $variantIndex) {
                
                //if imageData has only index element
                if (count($imageData) < 2 ) {
                    continue;
                }
                                
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
                        throw new \Exception(sprintf("Media by mediaId %s not found for article with number %s", 
                                $imageData['mediaId'], $article->getMainDetail()->getNumber()));
                    }
                    
                    $imageModel = $this->createNewArticleImage($article, $media);
                }
                
                $imageModel->fromArray($imageData);
                
                $images[] = $imageModel;
                unset($data[$key]);
            }
        }
        
        if ($images === null) {
            return;
        }
        
        $hasMain = $this->getCollectionElementByProperty($images, 'main', 1);
        
        if (!$hasMain) {
            $image = $images[0];
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
        if ($similars == null) {
            return;
        }
        
        $similarCollection = array();

        foreach ($similars as $index => $similar) {
            if ($similar['parentIndexElement'] != $similarIndex) {
                continue;
            }

            if (!isset($similar['similarId']) || !$similar['similarId']) {
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
    
    public function prepareArticleConfigurators(&$configurators, $configuratorIndex, $article)
    {
        if ($configurators == null) {
            return;
        }
        
        $configuratorSet = null;
        $optionPosition = 0;
        
        foreach ($configurators as $index => $configurator) {
            if ($configurator['parentIndexElement'] != $configuratorIndex) {
                continue;
            }

            if (!isset($configurator['configSetName'])) {
                continue;
            }
            
            if (!$configuratorSet) {
                $configuratorSet = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Set')
                        ->findOneBy(array('name' => $configurator['configSetName']));
            }

            if (!$configuratorSet) {
                $configuratorSet = $article->getConfiguratorSet();
            }
            
            if (!$configuratorSet) {
                $configuratorSet = new Configurator\Set();
                $number = $article->getMainDetail()->getNumber();
                
                if (isset($configurator['configSetName'])) {
                    $configuratorSet->setName($configurator['configSetName']);
                } else {
                    $configuratorSet->setName('Set-' . $number);
                }
                
                if (isset($configurator['configSetType'])) {
                    $configuratorSet->setType($configurator['configSetType']);
                }
                $configuratorSet->setPublic(false);
            }
            
            
            //configurator group
            $groupPosition = 0;
            if (isset($configurator['configGroupId'])) {
                $group = $this->getManager()
                        ->getRepository('Shopware\Models\Article\Configurator\Group')
                        ->find($configurator['configGroupId']);
                if (!$group) {
                    throw new \Exception(sprintf("ConfiguratorGroup by id %s not found", $configurator['configGroupId']));
                }
            } elseif (isset($configurator['configGroupName'])) {
                $group = $this->getManager()
                        ->getRepository('Shopware\Models\Article\Configurator\Group')
                        ->findOneBy(array('name' => $configurator['configGroupName']));

                if (!$group) {
                    $group = new Configurator\Group();
                    $group->setPosition($groupPosition);
                }
            } else {
                throw new \Exception('At least the groupname is required');
            }
            
            //configurator option
            if (isset($configurator['configOptionId'])) {
                $optionModel = $this->getManager()
                        ->find('Shopware\Models\Article\Configurator\Option', $configurator['configOptionId']);
                if (!$optionModel) {
                    throw new \Exception(sprintf("ConfiguratorOption by id %s not found", $configurator['configOptionId']));
                }
            } else {
                $optionModel = $this->getManager()
                        ->getRepository('Shopware\Models\Article\Configurator\Option')->findOneBy(array(
                            'name' => $configurator['configOptionName'],
                            'groupId' => $group->getId()
                        ));
            }
            
            $optionData = array(
                'id' => $configurator['configOptionId'],
                'name' => $configurator['configOptionName'],
            );
            
            if (!$optionModel) {
                $optionModel = new Configurator\Option();
            }
            
            $optionModel->fromArray($optionData);
            $optionModel->setGroup($group);
            $optionModel->setPosition($optionPosition++);
            
            $groupData = array(
                'id' => $configurator['configGroupId'],                
                'name' => $configurator['configGroupName'],                
                'options' => array($optionModel)                
            );
            
            $mainDetail = $article->getMainDetail();
            
            if (!$this->getAvailableOption($mainDetail->getConfiguratorOptions(), $optionData)) {
                $mainDetail->setConfiguratorOptions(array($optionModel));
            }
            
            $group->fromArray($groupData);
            $configuratorSet->setOptions(array($optionModel));
            $configuratorSet->setGroups(array($group));
            $this->getManager()->persist($configuratorSet);
            
            unset($optionModel);
            unset($group);
            unset($configurators[$index]);
        }
        return $configuratorSet;
    }
    
    public function prepareVariantConfigurators(&$configurators, $configuratorIndex, $article)
    {
        if ($configurators == null) {
            return;
        }
        
        foreach ($configurators as $index => $configurator) {
            if ($configurator['parentIndexElement'] != $configuratorIndex) {
                continue;
            }
            
            if (!isset($configurator['configOptionName']) || empty($configurator['configOptionName'])) {
                continue;
            }

            if (!$article->getConfiguratorSet()) {
                $articleNumber = $article->getMainDetail()->getNumber();
                throw new \Exception(sprintf('A configurator set has to be defined on article %s', $articleNumber));
            }
            
            $availableGroups = $article->getConfiguratorSet()->getGroups();
            
            $availableGroup = $this->getAvailableGroup($availableGroups, array(
                'id' => $configurator['configGroupId'],
                'name' => $configurator['configGroupName']
            ));
            
            //group is in the article configurator set configured?
            if (!$availableGroup) {
                continue;
            }
            
            //check if the option is available in the configured article configurator set.
            $option = $this->getAvailableOption($availableGroup->getOptions(), array(
                'id'   => $configurator['configOptionId'],
                'name' => $configurator['configOptionName']
            ));
            
            if (!$option) {
                $option = $this->getManager()
                        ->getRepository('Shopware\Models\Article\Configurator\Option')->findOneBy(array(
                            'name' => $configurator['configOptionName'],
                            'groupId' => $availableGroup->getId()
                        ));
            }

            if (!$option) {
                
                $option = new Configurator\Option();
                $option->setPosition(0);
                $option->setName($configurator['configOptionName']);
                $option->setGroup($availableGroup);
                $this->getManager()->persist($option);
            }
            
            $optionData[] = $option;            
        }

        return $optionData;
    }
    
    /**
     * Checks if the passed group data is already existing in the passed array collection.
     * The group data are checked for "id" and "name".
     *
     * @param ArrayCollection $availableGroups
     * @param array $groupData
     * @return bool|Group
     */
    private function getAvailableGroup(ArrayCollection $availableGroups, array $groupData)
    {
        /**@var $availableGroup Option */
        foreach ($availableGroups as $availableGroup) {
            if ( ($availableGroup->getName() == $groupData['name'] && $groupData['name'] !== null)
                || ($availableGroup->getId() == $groupData['id']) && $groupData['id'] !== null) {

                return $availableGroup;
            }
        }

        return false;
    }

    /**
     * Checks if the passed option data is already existing in the passed array collection.
     * The option data are checked for "id" and "name".
     *
     * @param \Doctrine\Common\Collections\ArrayCollection $availableOptions
     * @param array $optionData
     * @return bool
     */
    private function getAvailableOption(ArrayCollection $availableOptions, array $optionData)
    {
        /**@var $availableOption Option */
        foreach ($availableOptions as $availableOption) {
            if ( ($availableOption->getName() == $optionData['name'] && $optionData['name'] !== null)
                || ($availableOption->getId() == $optionData['id'] && $optionData['id'] !== null)) {

                return $availableOption;
            }
        }

        return false;
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
        return array_merge($this->getArticleVariantColumns(), $this->getVariantColumns());
    }
    
    public function getArticleVariantColumns()
    {
        $columns = array(
            'article.id as articleId',
            'article.name as name',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            "DATE_FORMAT(article.added, '%Y-%m-%d %H:%i:%s') as date ",
            'article.active as active',
            'article.pseudoSales as pseudoSales',
            'article.highlight as topSeller',
//            'article.metaTitle as metaTitle',
            'article.keywords as keywords',
            "DATE_FORMAT(article.changed, '%Y-%m-%d %H:%i:%s') as changeTime",
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
    
    public function getColumns($section)
    {
        $method = 'get' . ucfirst($section) . 'Columns';
        
        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    public function getParentKeys($section)
    {
        switch ($section) {
            case 'article':
                return array(
                    'article.id as articleId',
                    'variant.id as variantId',
                    'variant.number as orderNumber',
                );
            case 'price':
                return array(
                    'prices.articleDetailsId as variantId',
                );
            case 'propertyValue':
                return array(
                    'article.id as articleId',
                );
            case 'similar':
                return array(
                    'article.id as articleId',
                );
            case 'image':
                return array(
                    'article.id as articleId',
                );
            case 'configurator':
                return array(
                    'variant.id as variantId',
                );
        }
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
    
    public function getConfiguratorColumns()
    {
        return array(
            'variant.id as variantId',
            'configuratorOptions.id as configOptionId',
            'configuratorOptions.name as configOptionName',
            'configuratorGroup.id as configGroupId',
            'configuratorGroup.name as configGroupName',
            'configuratorGroup.description as configGroupDescription',
            'configuratorSet.name as configSetName',
            'configuratorSet.type as configSetType',
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
     * Returns deatil repositorys_core_shops
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