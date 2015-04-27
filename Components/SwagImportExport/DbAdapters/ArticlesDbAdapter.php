<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\ArticleWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\CategoryWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\ConfiguratorWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\PriceWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\RelationWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\Write;
use Shopware\Models\Article\Article as ArticleModel;
use Shopware\Models\Article\Detail as DetailModel;
use Shopware\Models\Article\Price as Price;
use Shopware\Models\Article\Image as Image;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Models\Article\Configurator;
use Shopware\Models\Media\Media as MediaModel;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\DataHelper as DataHelper;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Models\Property;

class ArticlesDbAdapter implements DataDbAdapter
{

    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    protected $db;

    /**
     * Shopware\Models\Article\Article
     */
    protected $repository;
    protected $variantRepository;
    protected $priceRepository;
    protected $groupRepository;

    //configurator
    protected $configGroupRepository;
    protected $configOptionRepository;

    protected $propertyGroupRepository;
    protected $propertyOptionRepository;
    protected $propertyValueRepository;

    //mappers
    protected $articleVariantMap;
    protected $variantMap;

    /**
     * @var array
     */
    protected $categoryIdCollection;

    /**
     * @var array
     */
    protected $unprocessedData;

    /**
     * @var array
     */
    protected $logMessages;

    /**
     * @var array
     */
    protected $tempData;

    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('detail.id');

        $builder->from('Shopware\Models\Article\Detail', 'detail')
            ->orderBy('detail.articleId', 'ASC')
            ->orderBy('detail.kind', 'ASC');

        if ($filter['variants']) {
            $builder->andWhere('detail.kind <> 3');
        } else {
            $builder->andWhere('detail.kind = 1');
        }

        if ($filter['categories']) {
            $category = $this->getManager()->find('Shopware\Models\Category\Category', $filter['categories'][0]);

            $this->collectCategoryIds($category);
            $categories = $this->getCategoryIdCollection();

            $categoriesBuilder = $manager->createQueryBuilder();
            $categoriesBuilder->select('article.id')
                ->from('Shopware\Models\Article\Article', 'article')
                ->leftjoin('article.categories', 'categories')
                ->where('categories.id IN (:cids)')
                ->setParameter('cids', $categories)
                ->groupBy('article.id');

            $articleIds = array_map(function($item) {
                return $item['id'];
            }, $categoriesBuilder->getQuery()->getResult());

            $builder->join('detail.article', 'article')
                ->andWhere('article.id IN (:ids)')
                ->setParameter('ids', $articleIds);
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
                ->get('adapters/articles_no_ids', 'Can not read articles without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_column_names', 'Can not read articles without column names.');
            throw new \Exception($message);
        }


        //articles
        $articleBuilder = $this->getArticleBuilder($columns['article'], $ids);

        $articles = $articleBuilder->getQuery()->getResult();

        $result['article'] = DbAdapterHelper::decodeHtmlEntities($articles);

        //prices
        $columns['price'] = array_merge(
            $columns['price'], array('customerGroup.taxInput as taxInput', 'articleTax.tax as tax')
        );

        $priceBuilder = $this->getPriceBuilder($columns['price'], $ids);

        $result['price'] = $priceBuilder->getQuery()->getResult();

        foreach ($result['price'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = round($record['price'] * (100 + $record['tax']) / 100, 2);
                $record['pseudoPrice'] = round($record['pseudoPrice'] * (100 + $record['tax']) / 100, 2);
            } else {
                $record['price'] = round($record['price'], 2);
                $record['pseudoPrice'] = round($record['pseudoPrice'], 2);
            }

            if ($record['basePrice']) {
                $record['basePrice'] = round($record['basePrice'], 2);
            }

            if (!$record['inStock']) {
                $record['inStock'] = '0';
            }
        }

        //images
        $imageBuilder = $this->getImageBuilder($columns['image'], $ids);
        $result['image'] = $imageBuilder->getQuery()->getResult();

        //filter values
        $propertyValuesBuilder = $this->getPropertyValueBuilder($columns['propertyValues'], $ids);
        $result['propertyValue'] = $propertyValuesBuilder->getQuery()->getResult();

        //configurator
        $configBuilder = $this->getConfiguratorBuilder($columns['configurator'], $ids);
        $result['configurator'] = $configBuilder->getQuery()->getResult();

        //similar
        $similarsBuilder = $this->getSimilarBuilder($columns['similar'], $ids);
        $result['similar'] = $similarsBuilder->getQuery()->getResult();

        //accessories
        $accessoryBuilder = $this->getAccessoryBuilder($columns['accessory'], $ids);
        $result['accessory'] = $accessoryBuilder->getQuery()->getResult();

        //categories
        $aritcleIds = $this->getManager()->createQueryBuilder()
            ->select('article.id')
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->groupBy('article.id');

        $mappedArticleIds = array_map(function($item) {
            return $item['id'];
        }, $aritcleIds->getQuery()->getResult());

        $categoryBuilder = $this->getCategoryBuilder($columns['category'], $mappedArticleIds);

        $result['category'] = $categoryBuilder->getQuery()->getResult();

        $result['translation'] = $this->prepareTranslationExport($ids);

        return $result;
    }

    public function prepareTranslationExport($ids)
    {
        //translations
        $translationVariantColumns = 'article.id as articleId, variant.id as variantId, variant.kind, ct.objectdata, ct.objectlanguage as languageId';
        $articleDetailIds = implode(',', $ids);

        $sql = "SELECT $translationVariantColumns FROM `s_articles_details` AS variant

                LEFT JOIN s_articles AS article
                ON article.id = variant.articleID

                LEFT JOIN s_core_translations AS ct
                ON variant.id = ct.objectkey AND objecttype = 'variant'

                WHERE variant.id IN ($articleDetailIds)
                ORDER BY languageId ASC
                ";

        $translations = $this->getDb()->query($sql)->fetchAll();

        $shops = $this->getShops();

        //removes default language
        unset($shops[0]);

        if (!empty($translations)) {

            $translationFields = array(
                "txtArtikel" => "name",
                "txtzusatztxt" => "additionalText",
                "metaTitle" => "metaTitle",
                "txtshortdescription" => "description",
                "txtlangbeschreibung" => "descriptionLong",
                "txtkeywords" => "keywords",
                "txtpackunit" => "packUnit"
            );

            //attributes
            $attributes =  $this->getTranslationAttr();

            if ($attributes){
                foreach ($attributes as $attr){
                    $translationFields[$attr['name']] = $attr['name'];
                }
            }

            $rows = array();
            foreach ($translations as $index => $record) {
                $articleId = $record['articleId'];
                $variantId = $record['variantId'];
                $languageId = $record['languageId'];
                $kind = $record['kind'];
                $rows[$variantId]['helper']['articleId'] = $articleId;
                $rows[$variantId]['helper']['variantKind'] = $kind;
                $rows[$variantId][$languageId]['articleId'] = $articleId;
                $rows[$variantId][$languageId]['variantId'] = $variantId;
                $rows[$variantId][$languageId]['languageId'] = $languageId;
                $rows[$variantId][$languageId]['variantKind'] = $kind;

                $objectdata = unserialize($record['objectdata']);

                if (!empty($objectdata)) {
                    foreach ($objectdata as $key => $value) {
                        if (isset($translationFields[$key])) {
                            $rows[$variantId][$languageId][$translationFields[$key]] = $value;
                        }
                    }
                }
            }
        }

        $result = array();

        foreach ($rows as $vId => $row) {
            $count = count($row) -1;
            if (count($shops) !== $count) {
                foreach ($shops as $shop) {
                    $shopId = $shop->getId();
                    if (isset($row[$shopId])) {
                        $result[] = $row[$shopId];
                    } else {
                        $result[] = array(
                            'articleId' => $row['helper']['articleId'],
                            'variantId' => $vId,
                            'languageId' => (string) $shopId,
                            'variantKind' => $row['helper']['variantKind'],
                        );
                        $index = count($result) - 1;
                        foreach($translationFields as $field){
                            $result[$index][$field] = '';
                        }
                    }
                }
            } else {
                unset($row['helper']);
                foreach ($row as $value) {
                    $result[] = $value;
                }
            }
        }

        $translationArticleColumns = 'article.id as articleId, ct.objectdata, ct.objectlanguage as languageId';

        $sql = "SELECT $translationArticleColumns FROM `s_articles_details` AS variant

                INNER JOIN s_articles AS article
                ON article.id = variant.articleID

                LEFT JOIN s_core_translations AS ct
                ON variant.id = ct.objectkey

                WHERE variant.id IN ($articleDetailIds) AND objecttype = 'article'
                GROUP BY ct.id
                ";
        $articles = $this->getDb()->query($sql)->fetchAll();


        foreach ($result as $index => $translation){
            foreach($articles as $article){
                //the translation for the main variant is coming
                //from article translations
                if ($translation['variantKind'] == 1
                    && $translation['articleId'] === $article['articleId']
                    && $translation['languageId'] === $article['languageId']){

                    $serializeData = unserialize($article['objectdata']);
                    foreach($translationFields as $key => $field){
                        $result[$index][$field] = $serializeData[$key];
                    }
                } else if($translation['articleId'] === $article['articleId'] && $translation['languageId'] === $article['languageId']){
                    $data = unserialize($article['objectdata']);
                    $result[$index]['name'] = $data['txtArtikel'];
                    $result[$index]['description'] = $data['txtshortdescription'];
                    $result[$index]['descriptionLong'] = $data['txtlangbeschreibung'];
                    $result[$index]['metaTitle'] = $data['metaTitle'];
                    $result[$index]['keywords'] = $data['txtkeywords'];
                }
            }
        }

        return $result;
    }

    public function getShops()
    {
        $shops = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findAll();

        return $shops;
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
        $columns['accessory'] = $this->getAccessoryColumns();
        $columns['configurator'] = $this->getConfiguratorColumns();
        $columns['category'] = $this->getCategoryColumns();
        $columns['translation'] = $this->getTranslationColumns();

        return $columns;
    }

    private function run($records)
    {
        Shopware()->Models()->getConnection()->beginTransaction();

        try {
            $this->performImport($records);
            Shopware()->Models()->getConnection()->commit();
        } catch(\Exception $e) {
            Shopware()->Models()->getConnection()->rollBack();
            throw $e;
        }
    }

    private function performImport($records)
    {
        $start = microtime(true);
        $articleWriter = new ArticleWriter();
        $pricesWriter = new PriceWriter();
        $categoryWriter = new CategoryWriter();
        $configuratorWriter = new ConfiguratorWriter();
        $relationWriter = new RelationWriter($this);

        foreach ($records['article'] as $index => $article) {
            try {
                $processedFlag = isset($article['processed']) && $article['processed'] == 1 ? true : false;
                if (!$processedFlag) {

                    list($articleId, $articleDetailId, $mainDetailId) = $articleWriter->write($article);

                    $pricesWriter->write(
                        $articleId,
                        $articleDetailId,
                        array_filter(
                            $records['price'],
                            function ($price) use ($index) {
                                return $price['parentIndexElement'] == $index;
                            }
                        )
                    );

                    $categoryWriter->write(
                        $articleId,
                        array_filter(
                            $records['category'],
                            function ($category) use ($index) {
                                return $category['parentIndexElement'] == $index && $category['categoryId'];
                            }
                        )
                    );

                    $configuratorWriter->write(
                        $articleId,
                        $articleDetailId,
                        $mainDetailId,
                        array_filter(
                            $records['configurator'],
                            function ($configurator) use ($index) {
                                return $configurator['parentIndexElement'] == $index;
                            }
                        )
                    );

                }

                $relationWriter->write(
                    $articleId,
                    $article['mainNumber'],
                    array_filter(
                        $records['accessory'],
                        function ($accessory) use ($index) {
                            return $accessory['parentIndexElement'] == $index;
                        }
                    ),
                    'accessory',
                    $processedFlag
                );

                $relationWriter->write(
                    $articleId,
                    $article['mainNumber'],
                    array_filter(
                        $records['similar'],
                        function ($similar) use ($index) {
                            return $similar['parentIndexElement'] == $index;
                        }
                    ),
                    'similar',
                    $processedFlag
                );
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }

        $end = microtime(true);
        error_log(print_r($end - $start, true)."\n", 3, Shopware()->DocPath().'/error.log');
    }

    /**
     * Writes articles into the database
     *
     * @param array $records
     * @throws \Exception
     * @throws AdapterException
     */
    public function write($records)
    {
        error_log(print_r($records, true) . "\n", 3, Shopware()->DocPath() . '/../error.log');

        //articles
        if (empty($records['article'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/no_records',
                'No article records were found.'
            );
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        $this->run($records);

        return;

        //articles
        if (empty($records['article'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/no_records', 'No article records were found.');
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesDbAdapter_Write',
            $records,
            array('subject' => $this)
        );

        foreach ($records['article'] as $index => $record) {

            try {
                unset($articleModel);
                unset($variantModel);
                unset($updateFlag);

                if (!isset($record['orderNumber']) && empty($record['orderNumber'])) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/ordernumber_required', 'Order number is required.');
                    throw new AdapterException($message);
                }

                $variantModel = $this->getVariantRepository()->findOneBy(array('number' => $record['orderNumber']));

                if ($variantModel) {
                    $articleModel = $variantModel->getArticle();
                } else if (isset($record['mainNumber']) && !empty($record['mainNumber']) && $record['mainNumber'] !== $record['orderNumber']) {
                    $mainVariant = $this->getVariantRepository()->findOneBy(array('number' => $record['mainNumber']));

                    if (!$mainVariant) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/articles/variant_existence', 'Variant with number %s does not exists.');
                        throw new AdapterException(sprintf($message, $record['mainNumber']));
                    }
                    $articleModel = $mainVariant->getArticle();
                } else if (isset($record['mainNumber']) && empty($record['mainNumber'])) {
                    $record['mainNumber'] = $record['orderNumber'];
                }

                if (!$articleModel) {
                    //creates artitcle and main variant
                    $articleModel = new ArticleModel();

                    $articleData = $this->prerpareArticle($record, $articleModel);

                    $mainDetailData = $this->prepareMainDetail($record, $articleData, $articleModel);

                    $articleModel->setMainDetail($mainDetailData);

                    $variantModel = $articleModel->getMainDetail();

                    $prices = $this->preparePrices($records['price'], $index, $variantModel, $articleModel, $articleData['tax']);
                    $variantModel->setPrices($prices);

                    $articleData['similar'] = $this->prepareSimilars($records['similar'], $index, $articleModel);
                    $articleData['related'] = $this->prepareAccessories($records['accessory'], $index, $articleModel);
                    $articleData['categories'] = $this->prepareCategories($records['category'], $index, $articleModel);
                    $articleData['images'] = $this->prepareImages($records['image'], $index, $articleModel);
                    $articleData['configuratorSet'] = $this->prepareArticleConfigurators($records['configurator'], $index, $articleModel);

                    $propertyValue = $this->preparePropertyValues($records['propertyValue'], $index, $articleModel);
                    if ($propertyValue) {
                        $articleData['propertyGroup'] = $propertyValue['group'];
                        $articleData['propertyValues'] = $propertyValue['values'];
                    }

                    //sets article_active from article_details_active
                    $articleData['active'] = $variantModel->getActive();

                    $articleModel->fromArray($articleData);

                    $violations = $this->getManager()->validate($articleModel);

                    if ($violations->count() > 0) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/articles/no_valid_article_entity', 'No valid detail entity for article %s');
                        throw new AdapterException(sprintf($message, $variantModel->getNumber()));
                    }

                    $this->getManager()->persist($articleModel);
                } else {

                    if ($variantModel) {
                        $updateFlag = true;
                    }

                    $processedFlag = isset($record['processed']) && $record['processed'] == 1 ? true : false;

                    //if it is main variant
                    //updates the also the article
                    if ($record['mainNumber'] === $record['orderNumber'] || $updateFlag) {
                        $articleData = $this->prerpareArticle($record, $articleModel);

                        $similar = $this->prepareSimilars($records['similar'], $index, $articleModel, $processedFlag);
                        if ($similar) {
                            $articleData['similar'] = $similar;
                        }

                        $accessories = $this->prepareAccessories($records['accessory'], $index, $articleModel, $processedFlag);
                        if ($accessories) {
                            $articleData['related'] = $accessories;
                        }

                        $categories = $this->prepareCategories($records['category'], $index, $articleModel);
                        if ($categories) {
                            $articleData['categories'] = $categories;
                        }

                        $images = $this->prepareImages($records['image'], $index, $articleModel);
                        if ($images) {
                            $articleData['images'] = $images;
                        }

                        $propertyValue = $this->preparePropertyValues($records['propertyValue'], $index, $articleModel);
                        if ($propertyValue) {
                            $articleData['propertyGroup'] = $propertyValue['group'];
                            $articleData['propertyValues'] = $propertyValue['values'];
                        }

                        $articleData['active'] = $variantModel->getActive();

                        $articleModel->fromArray($articleData);
                    }

                    //Variants
                    $variantModel = $this->prerpareVariant($record, $articleModel, $variantModel);

                    if ($record['mainNumber'] || $updateFlag) {
                        $configuratorOptions = $this->prepareVariantConfigurators($records['configurator'], $index, $articleModel);
                        if ($configuratorOptions) {
                            $variantModel->setConfiguratorOptions($configuratorOptions);
                        }
                    }

                    $prices = $this->preparePrices($records['price'], $index, $variantModel, $articleModel, $articleModel->getTax(), $updateFlag);
                    if ($prices) {
                        $variantModel->setPrices($prices);
                    }

                    $violations = $this->getManager()->validate($variantModel);

                    if ($violations->count() > 0) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/articles/no_valid_detail_entity', 'No valid detail entity for article %s');
                        throw new AdapterException(sprintf($message, $variantModel->getNumber()));
                    }

                    $this->getManager()->persist($variantModel);
                }

                $this->getManager()->flush();

                $articleTranslationData = array(
                    'articleId' => $articleModel->getId(),
                    'variantId' => $variantModel->getId(),
                    'kind' => $variantModel->getKind()
                );

                $this->getManager()->clear();

                $this->writeTranslations($records['translation'], $index, $articleTranslationData);

            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @param $translations
     * @param $translationIndex
     * @param $articleData
     * @throws \Shopware\Components\SwagImportExport\Exception\AdapterException
     */
    public function writeTranslations($translations, $translationIndex, $articleData)
    {
        if ($translations == null) {
            return;
        }

        $whiteList = array(
            'name',
            'description',
            'descriptionLong',
            'metaTitle',
            'keywords',
        );

        $variantWhiteList = array(
            'additionalText',
            'packUnit',
        );

        $whiteList = array_merge($whiteList, $variantWhiteList);

        //attributes
        $attributes =  $this->getTranslationAttr();

        if ($attributes){
            foreach ($attributes as $attr){
                $whiteList[] = $attr['name'];
                $variantWhiteList[] = $attr['name'];
            }
        }

        $translationWriter = new \Shopware_Components_Translation();

        foreach ($translations as $i => $translation) {
            if ($translation['parentIndexElement'] === $translationIndex) {

                if (!isset($translation['languageId']) || empty($translation['languageId'])){
                    continue;
                }

                $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $translation['languageId']);
                if (!$shop) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
                    throw new AdapterException(sprintf($message, $translation['languageId']));
                }

                if ($articleData['kind'] === 1){
                    $data = array_intersect_key($translation, array_flip($whiteList));
                    $translationWriter->write($shop->getId(), 'article', $articleData['articleId'], $data);
                } else {
                    $data = array_intersect_key($translation, array_flip($variantWhiteList));

                    //checks for empty translations
                    if (!empty($data)){
                        foreach($data as $index => $rows){
                            //removes empty rows
                            if(empty($rows)){
                                unset($data[$index]);
                            }
                        }
                    }

                    //saves if there is available data
                    if (!empty($data)){
                        $translationWriter->write($shop->getId(), 'variant', $articleData['variantId'], $data);
                    }
                }

            }
        }
    }

    public function getShop($id)
    {
        $shop = $this->getManager()->find('Shopware\Models\Shop\Shop', $id);
        if (!$shop) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
            throw new AdapterException(sprintf($message, $id));
        }

        return $shop;
    }

    public function getSections()
    {
        return array(
            array('id' => 'article', 'name' => 'article'),
            array('id' => 'price', 'name' => 'price'),
            array('id' => 'image', 'name' => 'image'),
            array('id' => 'propertyValue', 'name' => 'propertyValue'),
            array('id' => 'similar', 'name' => 'similar'),
            array('id' => 'accessory', 'name' => 'accessory'),
            array('id' => 'configurator', 'name' => 'configurator'),
            array('id' => 'category', 'name' => 'category'),
            array('id' => 'translation', 'name' => 'translation')
        );
    }

    public function prerpareArticle(&$data, $article)
    {
        $articleData = array();

        //check if a tax id is passed and load the tax model or set the tax parameter to null.
        if (!empty($data['taxId'])) {
            $articleData['tax'] = $this->getManager()->find('Shopware\Models\Tax\Tax', $data['taxId']);

            if (empty($data['tax'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/no_tax_id', 'Tax by id %s not found for article %s');
                throw new AdapterException(sprintf($message, $data['taxId'], $data['orderNumber']));
            }
        } elseif (!empty($data['tax'])) {
            $tax = $this->getManager()->getRepository('Shopware\Models\Tax\Tax')->findOneBy(array('tax' => $data['tax']));
            if (!$tax) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/no_tax_found', 'Tax by taxrate %s not found for article %s');
                throw new AdapterException(sprintf($message, $data['tax'], $data['orderNumber']));
            }
            $articleData['tax'] = $tax;
        } else {
            if (!$article->getTax()) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/no_tax_provided', 'Tax not provided for article %s');
                throw new AdapterException(sprintf($message, $data['orderNumber']));
            }
        }
        unset($data['tax']);

        //check if a supplier id is passed and load the supplier model or set the supplier parameter to null.
        if (!empty($data['supplierId'])) {
            $articleData['supplier'] = $this->getManager()->find('Shopware\Models\Article\Supplier', $data['supplierId']);
            if (empty($articleData['supplier'])) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/supplier_not_found', 'Supplier by id %s not found for article %s');
                throw new AdapterException(sprintf($message, $data['supplierId'], $data['orderNumber']));
            }
        } elseif (!empty($data['supplierName'])) {
            $supplier = $this->getManager()->getRepository('Shopware\Models\Article\Supplier')->findOneBy(array('name' => $data['supplierName']));
            if (!$supplier) {
                $supplier = new \Shopware\Models\Article\Supplier();
                $supplier->setName($data['supplierName']);
            }
            $articleData['supplier'] = $supplier;
        } else {
            if (!$article->getSupplier()) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/supplier_not_provided', 'Supplier not provided for article %s');
                throw new AdapterException(sprintf($message, $data['orderNumber']));
            }
        }
        unset($data['supplierName']);

        //check if a priceGroup id is passed and load the priceGroup model or set the priceGroup parameter to null.
        if (isset($data['priceGroupId'])) {
            if (empty($data['priceGroupId'])) {
                $articleData['priceGroupId'] = null;
            } else {
                $articleData['priceGroup'] = $this->getManager()->find('Shopware\Models\Price\Group', $data['priceGroupId']);
                if (empty($articleData['priceGroup'])) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/pricegroup_not_found', 'Pricegroup by id %s not found for article %s');
                    throw new AdapterException(sprintf($message, $data['priceGroupId'], $data['orderNumber']));
                }
            }
            unset($data['priceGroup']);
        }

        //check if a propertyGroup is passed and load the propertyGroup model or set the propertyGroup parameter to null.
        if (isset($data['propertyGroupId'])) {
            if (empty($data['propertyGroupId'])) {
                $articleData['propertyGroup'] = null;
            } else {
                $articleData['propertyGroup'] = $this->getManager()->find('\Shopware\Models\Property\Group', $data['filterGroupId']);

                if (empty($articleData['propertyGroup'])) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/propertyGroup_not_found', 'PropertyGroup by id %s not found for article %s');
                    throw new AdapterException(sprintf($message, $data['filterGroupId'], $data['orderNumber']));
                }
            }
            unset($data['propertyGroupId']);
        }

        $articleMap = $this->getMap('articleVariant');

        //fixes date time mappings
        $articleMap['date'] = 'added';
        $articleMap['changeTime'] = 'changed';

        foreach ($data as $key => $value) {
            if (isset($articleMap[$key])) {
                $articleData[$articleMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $articleData;
    }

    /**
     * @param array $data
     * @param array $articleData
     * @param ArticleModel $article
     * @return array
     */
    public function prepareMainDetail($data, $articleData, ArticleModel $article)
    {
        $variantData = array();
        $variantsMap = $this->getMap('variant');

        //fixes date time mapping
        $variantsMap['releaseDate'] = 'releaseDate';

        if ($articleData['active']) {
            $variantData['active'] = 1;
        }

        foreach ($data as $key => $value) {
            if (preg_match('/^attribute/', $key)) {
                $newKey = lcfirst(preg_replace('/^attribute/', '', $key));
                $variantData['attribute'][$newKey] = $value;
                unset($data[$key]);
            } else if(isset($variantsMap[$key])) {
                $variantData[$variantsMap[$key]] = $value;
            }
        }

        $variantData['attribute']['article'] = $article;

        $variantData['article'] = $articleData;

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

        //fixes date time mapping
        $variantsMap['releaseDate'] = 'releaseDate';

        foreach ($data as $key => $value) {
            if (preg_match('/^attribute/', $key)) {
                $newKey = lcfirst(preg_replace('/^attribute/', '', $key));
                $variantData['attribute'][$newKey] = $value;
                unset($data[$key]);
            } else if (isset($variantsMap[$key]) && $key != 'mainNumber') {
                $variantData[$variantsMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        if (!$variantModel->getAttribute()) {
            $variantData['attribute']['article'] = $article;
        }

        $variantModel->fromArray($variantData);
        $this->getManager()->persist($variantModel);

        return $variantModel;
    }

    public function preparePrices(&$data, $variantIndex, $variant, ArticleModel $article, $tax, $updateFlag = false)
    {
        if ($data === null && $updateFlag){
            return;
        } else if ($data === null) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/no_price_data', 'No price data found for article %s');
            throw new AdapterException(sprintf($message, $variant->getNumber()));
        }

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
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/customerGroup_not_found', 'Customer Group by key %s not found for article %s');
                    throw new AdapterException(sprintf($message, $priceData['priceGroup'], $variant->getNumber()));
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

                if (!isset($priceData['price']) && empty($priceData['price'])) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/incorrect_price', 'Price value is incorrect for article with nubmer %s');
                    throw new AdapterException(sprintf($message . $variant->getNumber()));
                }

                if ($priceData['from'] <= 0) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/invalid_price', 'Invalid Price "from" value for article %s');
                    throw new AdapterException(sprintf($message, $variant->getNumber()));
                }

                $oldPrice = $this->getPriceRepository()->findOneBy(
                    array(
                        'articleDetailsId' => $variant->getId(),
                        'customerGroupKey' => $priceData['priceGroup'],
                        'from' => $priceData['from']
                    )
                );

                $priceData['price'] = floatval(str_replace(",", ".", $priceData['price']));

                if (isset($priceData['basePrice'])) {
                    $priceData['basePrice'] = floatval(str_replace(",", ".", $priceData['basePrice']));
                } else {
                    if ($oldPrice) {
                        $priceData['basePrice'] = $oldPrice->getBasePrice();
                    }
                }

                if (isset($priceData['pseudoPrice'])) {
                    $priceData['pseudoPrice'] = floatval(str_replace(",", ".", $priceData['pseudoPrice']));
                } else {
                    if ($oldPrice) {
                        $priceData['pseudoPrice'] = $oldPrice->getPseudoPrice();
                    } else {
                        $priceData['pseudoPrice'] = 0;
                    }

                    if ($customerGroup->getTaxInput()) {
                        $priceData['pseudoPrice'] = round($priceData['pseudoPrice'] * (100 + $tax->getTax()) / 100, 2);
                    }
                }

                if (isset($priceData['percent'])) {
                    $priceData['percent'] = floatval(str_replace(",", ".", $priceData['percent']));
                } else {
                    if ($oldPrice) {
                        $priceData['percent'] = $oldPrice->getPercent();
                    }
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
                unset($oldPrice);
            }
        }

        return $prices;
    }

    public function prepareCategories(&$data, $variantIndex, ArticleModel $article)
    {
        if ($data == null) {
            return;
        }

        $articleCategories = $article->getCategories();

        foreach ($data as $key => $categoryData) {

            if ($categoryData['parentIndexElement'] === $variantIndex) {

                if (!isset($categoryData['categoryId']) || !$categoryData['categoryId']) {
                    continue;
                }

                foreach ($articleCategories as $articleCategory) {
                    if ($articleCategory->getId() == (int) $categoryData['categoryId']) {
                        continue;
                    }
                }

                $categoryModel = $this->getManager()->find(
                    'Shopware\Models\Category\Category', (int) $categoryData['categoryId']
                );

                if (!$categoryModel) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/category_not_found', 'Category with id %s could not be found.');
                    throw new AdapterException(sprintf($message, $categoryData['categoryId']));
                }


                $categories[] = $categoryModel;
                unset($categoryModel);
                unset($data[$key]);
            }
        }

        if ($categories === null) {
            return;
        }

        return $categories;
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
                    if (isset($imageData['mediaId']) && !empty($imageData['mediaId'])) {
                        $media = $this->getManager()->find(
                            'Shopware\Models\Media\Media', (int) $imageData['mediaId']
                        );
                    } elseif (isset($imageData['path']) && !empty($imageData['path'])){
                        $media = $this->getMediaRepository()->findOneBy(array('name' => $imageData['path']));
                    } elseif (isset($imageData['imageUrl']) && !empty($imageData['imageUrl'])){
                        $name = pathinfo($imageData['imageUrl'], PATHINFO_FILENAME);

                        $media = $this->getMediaRepository()->findOneBy(array('name' => $name));
                        if (!$media){
                            $thumbnail = isset($record['thumbnail']) && $record['thumbnail'] == 0 ? 0 : 1;
                            $modifyData = array(
                                'ordernumber' => $article->getMainDetail()->getNumber(),
                                'image' => $imageData['imageUrl'],
                                'thumbnail' => $thumbnail
                            );

                            $this->setUnprocessedData('articlesImages', 'default', $modifyData);
                        }
                    }

                    if (!($media instanceof MediaModel)) {
                        continue;
                    }

                    if ($this->isImageExists($article->getImages(), $media)){
                        continue;
                    }

                    $imageModel = $this->createNewArticleImage($article, $media);
                }

                $imageModel->fromArray($imageData);

                unset($imageModel);
                unset($data[$key]);
            }
        }

        $images = $article->getImages();

        if (!count($images)) {
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
     * Checks is image already exists in article
     *
     * @param $imageCollection
     * @param $media
     * @return bool
     */
    public function isImageExists($imageCollection, $media)
    {
        $mediaName = $media->getName();
        foreach ($imageCollection as $image){
            if ($image->getPath() === $mediaName){
                return true;
            }
        }

        return false;
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

    /**
     * @param array $propertyValues
     * @param int $propertyValueIndex
     * @param ArticleModel $article
     */
    public function preparePropertyValues(&$propertyValues, $propertyValueIndex, ArticleModel $article)
    {
        if ($propertyValues == null) {
            return;
        }

        $propertyRepository = $this->getManager()->getRepository('Shopware\Models\Property\Group');

        $hydrateObject = 1;
        $values = array();

        foreach ($propertyValues as $index => $valueData) {
            if ($valueData['parentIndexElement'] != $propertyValueIndex) {
                continue;
            }

            $propertyGroup = $article->getPropertyGroup();

            if (!isset($valueData['propertyGroupName']) && empty($valueData['propertyGroupName']) && !$propertyGroup){
                continue;
            } else if (isset($valueData['propertyGroupName']) && !$propertyGroup) {
                $propertyGroup = $propertyRepository->findOneBy(array('name' => $valueData['propertyGroupName']));

                if (!$propertyGroup){
                    //creates property group
                    $propertyGroup = $this->createPropertyGroup($valueData);
                }
            }

            if (!$propertyGroup instanceof Property\Group) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/property_group_name_not_found', 'There is no propertyGroupName specified for article %s');
                throw new AdapterException(sprintf($message, $article->getMainDetail()->getNumber()));
            }

            $value = null;
            /** @var \Shopware\Models\Property\Option $option */
            $option = null;

            if (isset($valueData['propertyGroupId'])) {
                $value = $this->getManager()->getRepository('\Shopware\Models\Property\Value')->find($valueData['propertyGroupId']);
                if (!$value) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/property_id_not_found', 'Property value by id %s not found for article %s');
                    throw new AdapterException(sprintf($message, $valueData['propertyGroupId'], $article->getMainDetail()->getNumber()));
                }
            } elseif (isset($valueData['propertyValueId'])) {
                $value = $this->getManager()->getRepository('\Shopware\Models\Property\Value')->findOneBy(array(
                    'id' => $valueData['propertyValueId']
                ));
                if (!$value) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/property_id_not_found', 'Property value by id %s not found for article %s');
                    throw new AdapterException(sprintf($message, $valueData['propertyGroupId'], $article->getMainDetail()->getNumber()));
                }
            } elseif (isset($valueData['propertyValueName'])) {
                if (isset($valueData['propertyOptionId'])) {
                    $option = $this->getManager()->getRepository('\Shopware\Models\Property\Option')->find($valueData['propertyOptionId']);
                    if (!$option) {
                        throw new AdapterException(sprintf("Property option by id %s not found", $valueData['propertyOptionId']));
                    }

                    $filters = array(
                        array('property' => "options.id", 'expression' => '=', 'value' => $option->getId()),
                        array('property' => "groups.id", 'expression' => '=', 'value' => $propertyGroup->getId()),
                    );

                    $query = $propertyRepository->getPropertyRelationQuery($filters, null, 1, 0);
                    /** @var \Shopware\Models\Property\Relation $relation */
                    $relation = $query->getOneOrNullResult($hydrateObject);

                    if (!$relation) {
                        $propertyGroup->addOption($option);
                    }
                } elseif (isset($valueData['propertyOptionName'])) {
                    // if a name is passed and there is a matching option/group relation, get this option
                    // if only a name is passed, create a new option
                    $filters = array(
                        array('property' => "options.name", 'expression' => '=', 'value' => $valueData['propertyOptionName']),
                        array('property' => "groups.name", 'expression' => '=', 'value' => $propertyGroup->getName()),
                    );
                    $query = $propertyRepository->getPropertyRelationQuery($filters, null, 1, 0);
                    /** @var \Shopware\Models\Property\Relation $relation */
                    $relation = $query->getOneOrNullResult($hydrateObject);

                    if (!$relation) {
                        $option = $this->createPropertyOption($valueData);
                        $propertyGroup->addOption($option);
                    } else {
                        $option = $relation->getOption();
                    }
                } else {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/property_option_required', 'A property option need to be given for each property value for article %s');
                    throw new AdapterException(sprintf($message, $article->getMainDetail()->getNumber()));
                }

                if ($option->isFilterable() === null) {
                    $option->setFilterable(false);
                }

                // If there is a filter value with matching name and option, load this value, else create a new one
                $value = $this->getManager()->getRepository('\Shopware\Models\Property\Value')->findOneBy(array(
                    'value' => $valueData['propertyValueName'],
                    'optionId' => $option->getId()
                ));
                if (!$value) {
                    // create the value
                    $value = new Property\Value($option, $valueData['propertyValueName']);
                }

                if (isset($valueData['propertyValuePosition'])) {
                    $value->setPosition($valueData['propertyValuePosition']);
                }

                $this->getManager()->persist($value);
                $this->getManager()->flush();
            } else {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/property_id_or_name_required', 'Article %s requires name or id for property value');
                throw new AdapterException(sprintf($message, $article->getMainDetail()->getNumber()));
            }

            $values[] = $value;

            unset($propertyValues[$index]);
        }

        return array(
            'group' => $propertyGroup,
            'values' => $values
        );
    }

    /**
     * @param array $data
     * @return Property\Group
     */
    public function createPropertyGroup($data)
    {
        $propertyGroup = new Property\Group();

        //todo: remove hardcode data
        $groupData = array(
            'name' => $data['propertyGroupName'],
            'comparable' => 0,
            'position' => 1,
            'sortMode' => 1,
        );
        $propertyGroup->fromArray($groupData);
        $this->getManager()->persist($propertyGroup);

        return $propertyGroup;
    }

    /**
     * @param $data
     * @return Property\Option
     */
    public function createPropertyOption($data)
    {
        $option = new Property\Option();
        $option->setName($data['propertyOptionName']);
        $option->setFilterable(false);
        $this->getManager()->persist($option);

        return $option;
    }

    /**
     * @param array $similars
     * @param int $similarIndex
     * @param object $article
     * @param int $processedFlag
     * @return array
     * @throws \Exception
     */
    public function prepareSimilars(&$similars, $similarIndex, $article, $processedFlag = false)
    {
        if ($similars == null) {
            return;
        }

        //merged existing similar with the unprocessed data
        if ($processedFlag) {
            $similarCollection = $article->getSimilar();
        } else {
            $similarCollection = array();
        }

        foreach ($similars as $index => $similar) {
            if ($similar['parentIndexElement'] != $similarIndex) {
                continue;
            }

            if ((!isset($similar['similarId']) || !$similar['similarId'])
                && (!isset($similar['ordernumber']) || !$similar['ordernumber'])) {
                continue;
            }

            if (isset($similar['ordernumber']) && $similar['ordernumber']) {
                $similarDetails = $this->getVariantRepository()
                    ->findOneBy(array('number' => $similar['ordernumber']));

                if (!$similarDetails && $processedFlag === true) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/similar_not_found', 'Similar with ordernumber %s does not exists');
                    throw new AdapterException(sprintf($message, $similar['ordernumber']));
                }

                if (!$similarDetails) {
                    $articleNumber = $article->getMainDetail()->getNumber();

                    //sets similar data for export
                    $similarData = array(
                        'articleId' => $articleNumber,
                        'ordernumber' => $similar['ordernumber'],
                    );
                    $this->saveUnprocessedData('articles', 'similar', $articleNumber, $similarData);
                    continue;
                }

                $similar['similarId'] = $similarDetails->getArticle()->getId();
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

    public function prepareAccessories(&$accessories, $accessoryIndex, $article, $processedFlag = false)
    {
        if ($accessories == null) {
            return;
        }

        //merged existing accessories with the unprocessed data
        if ($processedFlag) {
            $accessoriesCollection = $article->getRelated();
        } else {
            $accessoriesCollection = array();
        }

        foreach ($accessories as $index => $accessory) {
            if ($accessory['parentIndexElement'] != $accessoryIndex) {
                continue;
            }

            if ((!isset($accessory['accessoryId']) || !$accessory['accessoryId'])
                && (!isset($accessory['ordernumber']) || !$accessory['ordernumber'])) {
                continue;
            }

            if (isset($accessory['ordernumber']) && $accessory['ordernumber']) {
                $accessoryDetails = $this->getVariantRepository()
                    ->findOneBy(array('number' => $accessory['ordernumber']));

                if (!$accessoryDetails && $processedFlag === true) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/accessory_not_found', 'Accessory with ordernumber %s does not exists');
                    throw new AdapterException(sprintf($message, $accessory['ordernumber']));
                }

                if (!$accessoryDetails) {
                    $articleNumber = $article->getMainDetail()->getNumber();

                    $data = array(
                        'articleId' => $articleNumber,
                        'ordernumber' => $accessory['ordernumber'],
                    );
                    $this->saveUnprocessedData('articles', 'accessory', $articleNumber, $data);
                    continue;
                }

                $accessory['accessoryId'] = $accessoryDetails->getArticle()->getId();
            }

            if ($this->isAccessoryArticleExists($article, $accessory['accessoryId'])) {
                continue;
            }

            $accessoryModel = $this->getManager()->getReference('Shopware\Models\Article\Article', $accessory['accessoryId']);

            $accessoriesCollection[] = $accessoryModel;

            unset($accessories[$index]);
        }

        return $accessoriesCollection;
    }

    public function prepareArticleConfigurators(&$configurators, $configuratorIndex, $article)
    {
        if ($configurators == null) {
            return;
        }

        $configuratorSet = null;

        foreach ($configurators as $index => $configurator) {
            if ($configurator['parentIndexElement'] != $configuratorIndex) {
                continue;
            }

            if (!isset($configurator['configOptionId']) || empty($configurator['configOptionId'])){
                if (!isset($configurator['configGroupName']) && !isset($configurator['configGroupId'])) {
                    continue;
                }

                if (empty($configurator['configGroupName']) && empty($configurator['configGroupId'])) {
                    continue;
                }

                if (!isset($configurator['configOptionName']) || empty($configurator['configOptionName'])) {
                    continue;
                }
            }

            if (isset($configurator['configSetId']) && !empty($configurator['configSetId']) && !$configuratorSet) {
                $configuratorSet = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Set')
                    ->findOneBy(array('id' => $configurator['configSetId']));
            }

            if ((!isset($configurator['configSetName']) || empty($configurator['configSetName'])) && !$configuratorSet) {
                $configuratorSet = $this->createConfiguratorSet($configurator, $article);
            }

            if (!$configuratorSet) {
                $configuratorSet = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Set')
                    ->findOneBy(array('name' => $configurator['configSetName']));
            }

            if (!$configuratorSet) {
                $configuratorSet = $article->getConfiguratorSet();
            }

            if (!$configuratorSet) {
                $configuratorSet = $this->createConfiguratorSet($configurator, $article);
            }

            //configurator option
            if (isset($configurator['configOptionId']) && !empty($configurator['configOptionId'])) {
                $optionModel = $this->getManager()
                    ->find('Shopware\Models\Article\Configurator\Option', $configurator['configOptionId']);

                if (!$optionModel) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/config_option_not_found', 'ConfiguratorOption with id %s not found');
                    throw new AdapterException(sprintf($message, $configurator['configOptionId']));
                }

                $groupModel = $optionModel->getGroup();
                $groupName = $groupModel->getName();
            } else {
                //configurator group
                $groupModel = $this->getConfiguratorGroup($configurator);
                $groupName = $configurator['configGroupName'];
            }

            if (isset($configurator['configOptionName']) && !$optionModel){
                $optionModel = $this->getManager()
                    ->getRepository('Shopware\Models\Article\Configurator\Option')->findOneBy(array(
                        'name' => $configurator['configOptionName'],
                        'groupId' => $groupModel->getId()
                    ));
            }

            if (!$optionModel) {
                if (isset($configurator['configOptionPosition']) && !empty($configurator['configOptionPosition'])){
                    $position = $configurator['configOptionPosition'];
                } else {
                    $position = 1;
                }

                $optionModel = new Configurator\Option();
                $optionData = array(
                    'id' => $configurator['configOptionId'],
                    'name' => $configurator['configOptionName'],
                    'position' => $position,
                );
                $optionModel->fromArray($optionData);
            }

            $optionModel->setGroup($groupModel);

            $groupData = array(
                'id' => $configurator['configGroupId'],
                'name' => $groupName,
                'options' => array($optionModel)
            );

            $groupModel->fromArray($groupData);

            $options[] = $optionModel;
            $groups[] = $groupModel;

            unset($optionModel);
            unset($groupModel);
            unset($configurators[$index]);
        }

        if ($configuratorSet && $groups && $options) {
            $article->getMainDetail()->setConfiguratorOptions($options);
            $configuratorSet->setOptions($options);
            $configuratorSet->setGroups($groups);
            $this->getManager()->persist($configuratorSet);
        }

        return $configuratorSet;
    }

    public function prepareVariantConfigurators(&$configurators, $configuratorIndex, $article)
    {
        if ($configurators == null) {
            return;
        }

        $configuratorSet = $article->getConfiguratorSet();
        $configSetFlag = false;
        $optionPosition = 1;

        foreach ($configurators as $index => $configurator) {
            if ($configurator['parentIndexElement'] != $configuratorIndex) {
                continue;
            }

            if (!isset($configurator['configOptionId']) || empty($configurator['configOptionId'])){

                if (!isset($configurator['configGroupName']) && !isset($configurator['configGroupId'])) {
                    continue;
                }

                if (empty($configurator['configGroupName']) && empty($configurator['configGroupId'])) {
                    continue;
                }

                if (!isset($configurator['configOptionName']) || empty($configurator['configOptionName'])) {
                    continue;
                }
            }

            if (!$configuratorSet) {
                $configuratorSet = $this->createConfiguratorSet($configurator, $article);
                $article->setConfiguratorSet($configuratorSet);
                $this->getManager()->persist($article);
                $this->getManager()->flush();
                $configSetFlag = true;
            }

            if ($configSetFlag === true) {
                $this->createConfiguratorGroupsAndOptionsFromVariant($configurator, $configuratorSet);
            }

            $setOptions = $configuratorSet->getOptions();

            if (isset($configurator['configOptionId']) && !empty($configurator['configOptionId'])){
                $option = $this->getManager()
                    ->find('Shopware\Models\Article\Configurator\Option', $configurator['configOptionId']);

                if (!$option) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articles/config_option_not_found', 'ConfiguratorOption with id %s not found');
                    throw new AdapterException(sprintf($message, $configurator['configOptionId']));
                }

            } else {

                $availableGroups = $configuratorSet->getGroups();

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
                    'id' => $configurator['configOptionId'],
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
                    if (isset($configurator['configOptionPosition']) && !empty($configurator['configOptionPosition'])){
                        $position = $configurator['configOptionPosition'];
                    } else {
                        $optionPosition++;
                        $position = $optionPosition;
                    }

                    //creates configuration option
                    $option = new Configurator\Option();
                    $option->setName($configurator['configOptionName']);
                    $option->setPosition($position);
                    $option->setGroup($availableGroup);
                    $this->getManager()->persist($option);
                }
            }

            $optionData[] = $option;

            //add option to configurator set if dont exists
            if (!$this->isEntityExistsByName($setOptions, $option)) {
                $setOptions->add($option);
            }
        }

        return $optionData;
    }

    /**
     *
     * @param array $data
     * @param \Shopware\Models\Article\Article $article
     * @return \Shopware\Models\Article\Configurator\Set
     */
    public function createConfiguratorSet($data, $article)
    {
        $configuratorSet = new Configurator\Set();

        if (isset($data['configSetName']) && !empty($data['configSetName'])) {
            $configuratorSet->setName($data['configSetName']);
        } else {
            $number = $article->getMainDetail()->getNumber();
            $configuratorSet->setName('Set-' . $number);
        }

        if (isset($data['configSetType'])) {
            $configuratorSet->setType($data['configSetType']);
        }

        $configuratorSet->setPublic(false);

        return $configuratorSet;
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
            if ( ($availableGroup->getId() == $groupData['id'] && $groupData['id'] !== null)
                || ($availableGroup->getName() == $groupData['name'] && $groupData['name'] !== null)) {

                return $availableGroup;
            }
        }

        //Double check
        //sometimes group name exist e.g. grÃ¶ÃŸe = grÃ¶sse
        if (isset($groupData['name'])) {
            $groupModel = $this->getManager()
                ->getRepository('Shopware\Models\Article\Configurator\Group')
                ->findOneBy(array('name' => $groupData['name']));
            if ($groupModel) {
                $groupId = $groupModel->getId();
                foreach ($availableGroups as $availableGroup) {
                    if ($availableGroup->getId() == $groupId) {
                        return $availableGroup;
                    }
                }
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

    public function isEntityExistsByName(ArrayCollection $models, $entity)
    {
        foreach ($models as $model) {
            if ($model->getName() == $entity->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $configurator
     * @param array $configuratorSet
     * @throws AdapterException
     * @throws \Exception
     */
    public function createConfiguratorGroupsAndOptionsFromVariant($configurator, $configuratorSet)
    {
        if (isset($configurator['configOptionId']) && !empty($configurator['configOptionId'])){
            $optionModel = $this->getManager()
                ->find('Shopware\Models\Article\Configurator\Option', $configurator['configOptionId']);

            if (!$optionModel) {
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/config_option_not_found', 'ConfiguratorOption with id %s not found');
                throw new AdapterException(sprintf($message, $configurator['configOptionId']));
            }

            $groupModel = $optionModel->getGroup();
        } else {
            $groupModel = $this->getConfiguratorGroup($configurator);
        }

        if (!$groupModel->getName()) {
            $groupModel->setName($configurator['configGroupName']);
        }

        $groups = $configuratorSet->getGroups();
        $groups->add($groupModel);
        $configuratorSet->setGroups($groups);

        if ($groupModel->getId() && !$optionModel) {
            $optionModel = $this->getManager()
                ->getRepository('Shopware\Models\Article\Configurator\Option')->findOneBy(array(
                    'name' => $configurator['configOptionName'],
                    'groupId' => $groupModel->getId()
                ));
        }

        if (!$optionModel) {
            $optionModel = $this->createConfiguratorOption($configurator, $groupModel);
        }

        $options = $configuratorSet->getOptions();
        $options->add($optionModel);
        $configuratorSet->setOptions($options);

        $this->getManager()->persist($configuratorSet);
        $this->getManager()->flush();
    }

    /**
     * Returns configurator group
     *
     * @param array $data
     * @return \Shopware\Models\Article\Configurator\Group
     * @throws \Exception
     */
    public function getConfiguratorGroup($data)
    {
        $groupPosition = 0;
        if (isset($data['configGroupId'])) {
            $groupModel = $this->getManager()
                ->getRepository('Shopware\Models\Article\Configurator\Group')
                ->find($data['configGroupId']);
        }

        if (isset($data['configGroupName']) && !$groupModel) {
            $groupModel = $this->getManager()
                ->getRepository('Shopware\Models\Article\Configurator\Group')
                ->findOneBy(array('name' => $data['configGroupName']));

            if (!$groupModel) {
                $groupModel = new Configurator\Group();
                $groupModel->setPosition($groupPosition);
            }
        }

        if (!$groupModel) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/provide_groupname_groupid', 'Please provide groupname or groupId');
            throw new AdapterException($message);
        }

        return $groupModel;
    }

    /**
     *
     * @param array $data
     * @param Shopware\Models\Article\Configurato\Group
     */
    public function createConfiguratorOption($data, $group)
    {
        $option = new Configurator\Option();
        $option->setPosition(0);
        $option->setName($data['configOptionName']);
        $option->setGroup($group);

        return $option;
    }

    public function isSimilarArticleExists($article, $similarId)
    {
        foreach ($article->getSimilar() as $similar){
            if ($similar->getId() == $similarId) {
                return true;
            }
        }

        return false;
    }

    public function isAccessoryArticleExists($article, $accessoryId)
    {
        foreach ($article->getRelated() as $accessory) {
            if ($accessory->getId() == $accessoryId) {
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
                $message = SnippetsHelper::getNamespace()
                    ->get('adapters/articles/method_not_found', 'Method %s not found on entity %s');
                throw new \Exception(sprintf($message, $method, get_class($entity)));
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
        return array(
            'article.id as articleId',
            'article.name as name',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            "DATE_FORMAT(article.added, '%Y-%m-%d') as date",
//            'article.active as active',
            'article.pseudoSales as pseudoSales',
            'article.highlight as topSeller',
            'article.metaTitle as metaTitle',
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
    }

    public function getArticleAttributes()
    {
        $stmt = $this->getDb()->query("SHOW COLUMNS FROM `s_articles_attributes`");
        $columns = $stmt->fetchAll();

        $attributes = array();
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id' && $column['Field'] !== 'articleID' && $column['Field'] !== 'articledetailsID') {
                $attributes[] = $column['Field'];
            }
        }

        if ($attributes) {
            $prefix = 'attribute';
            $attributesSelect = array();
            foreach ($attributes as $attribute) {
                //underscore to camel case
                //exmaple: underscore_to_camel_case -> underscoreToCamelCase

                $attr = preg_replace("/\_(.)/e", "strtoupper('\\1')", $attribute);

                $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $attr, ucwords($attr));
            }
        }

        return $attributesSelect;
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
            case 'accessory':
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
            case 'category':
                return array(
                    'article.id as articleId',
                );
            case 'translation':
                return array(
                    'variant.id as variantId',
                );
        }
    }

    public function getVariantColumns()
    {
        $columns = array(
            'variant.id as variantId',
            'variant.number as orderNumber',
            'mv.number as mainNumber',
            'variant.kind as kind',
            'variant.additionalText as additionalText',
            'variant.inStock as inStock',
            'variant.active as active',
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
            "DATE_FORMAT(variant.releaseDate, '%Y-%m-%d') as releaseDate",
            'variant.shippingTime as shippingTime',
            'variant.shippingFree as shippingFree',
            'variant.supplierNumber as supplierNumber',
        );

        // Attributes
        $attributesSelect = $this->getArticleAttributes();

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
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
        $request = Shopware()->Front()->Request();
        $path = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/media/image/';

        return array(
            'images.id as id',
            'images.articleId as articleId',
            'images.articleDetailId as variantId',
            'images.path as path',
            "CONCAT('$path', images.path, '.', images.extension) as imageUrl",
            'images.main as main',
            'images.mediaId as mediaId',
            ' \'1\' as thumbnail'
        );
    }

    public function getPropertyValueColumns()
    {
        return array(
            'article.id as articleId',
            'propertyGroup.name as propertyGroupName',
            'propertyValues.id as propertyValueId',
            'propertyValues.value as propertyValueName',
            'propertyValues.position as propertyValuePosition',
            'propertyValues.valueNumeric as propertyValueNumeric',
            'propertyOptions.name as propertyOptionName',
        );
    }

    public function getSimilarColumns()
    {
        return array(
            'similar.id as similarId',
            'similarDetail.number as ordernumber',
            'article.id as articleId',
        );
    }

    public function getAccessoryColumns()
    {
        return array(
            'accessory.id as accessoryId',
            'accessoryDetail.number as ordernumber',
            'article.id as articleId',
        );
    }

    public function getConfiguratorColumns()
    {
        return array(
            'variant.id as variantId',
            'configuratorOptions.id as configOptionId',
            'configuratorOptions.name as configOptionName',
            'configuratorOptions.position as configOptionPosition',
            'configuratorGroup.id as configGroupId',
            'configuratorGroup.name as configGroupName',
            'configuratorGroup.description as configGroupDescription',
            'configuratorSet.id as configSetId',
            'configuratorSet.name as configSetName',
            'configuratorSet.type as configSetType',
        );
    }

    public function getCategoryColumns()
    {
        return array(
            'categories.id as categoryId',
            'article.id as articleId',
        );
    }

    public function getTranslationColumns()
    {
        $columns = array(
            'article.id as articleId',
            'variant.id as variantId',
            'translation.objectlanguage as languageId',
            'translation.name as name',
            'translation.keywords as keywords',
            'translation.metaTitle as metaTitle',
            'translation.description as description',
            'translation.description_long as descriptionLong',
            'translation.additionalText as additionalText',
            'translation.packUnit as packUnit',
        );

        $attributes = $this->getTranslationAttr();

        if($attributes){
            foreach ($attributes as $attr){
                $columns[] = $attr['name'];
            }
        }

        return $columns;
    }

    public function getTranslationAttr()
    {
        $elementBuilder = $this->getElementBuilder();

        return $elementBuilder->getQuery()->getArrayResult();
    }

    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
    }

    public function getLogMessages()
    {
        return $this->logMessages;
    }

    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
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

    public function getCategoryIdCollection()
    {
        return $this->categoryIdCollection;
    }

    public function setCategoryIdCollection($categoryIdCollection)
    {
        $this->categoryIdCollection[] = $categoryIdCollection;
    }

    public function saveUnprocessedData($profileName, $type, $articleNumber, $data)
    {
        $this->saveArticleData($articleNumber);

        $this->setUnprocessedData($profileName, $type, $data);
    }

    /**
     * This data is for matching similars and accessories
     *
     * @param $articleNumber
     */
    protected function saveArticleData($articleNumber)
    {
        $tempData = $this->getTempData();

        if (isset($tempData[$articleNumber])) {
            return;
        }

        $this->setTempData($articleNumber);

        $articleData = array(
            'articleId' => $articleNumber,
            'orderNumber' => $articleNumber,
//            'mainNumber' => $articleNumber,
            'processed' => 1
        );

        $this->setUnprocessedData('articles', 'article', $articleData);
    }

    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    public function setUnprocessedData($profileName, $type, $data)
    {
        $this->unprocessedData[$profileName][$type][] = $data;
    }

    public function getTempData()
    {
        return $this->tempData;
    }

    public function setTempData($tempData)
    {
        $this->tempData[$tempData] = $tempData;
    }

    /**
     * Returns article repository
     *
     * @return \Shopware\Models\Article\Article
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
     * @return \Shopware\Models\Article\Detail
     */
    public function getVariantRepository()
    {
        if ($this->variantRepository === null) {
            $this->variantRepository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->variantRepository;
    }

    /**
     * Returns price repository
     *
     * @return \Shopware\Models\Article\Price
     */
    public function getPriceRepository()
    {
        if ($this->priceRepository === null) {
            $this->priceRepository = $this->getManager()->getRepository('Shopware\Models\Article\Price');
        }

        return $this->priceRepository;
    }

    /**
     * Returns group repository
     *
     * @return \Shopware\Models\Customer\Group
     */
    public function getGroupRepository()
    {
        if ($this->groupRepository === null) {
            $this->groupRepository = $this->getManager()->getRepository('Shopware\Models\Customer\Group');
        }

        return $this->groupRepository;
    }

    /**
     * Returns media repository
     *
     * @return \Shopware\Models\Media\Media
     */
    public function getMediaRepository()
    {
        if ($this->mediaRepository === null) {
            $this->mediaRepository = $this->getManager()->getRepository('Shopware\Models\Media\Media');
        }

        return $this->mediaRepository;
    }

    /**
     * @return Shopware\Models\Article\Configurator\Group
     */
    public function getConfigGroupRepository(){
        if ($this->configGroupRepository === null){
            $this->configGroupRepository = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Group');
        }

        return $this->configGroupRepository;
    }

    /**
     * @return Shopware\Models\Article\Configurator\Option
     */
    public function getConfigOptionRepository(){
        if ($this->configOptionRepository === null){
            $this->configOptionRepository = $this->getManager()->getRepository('Shopware\Models\Article\Configurator\Option');
        }

        return $this->configOptionRepository;
    }

    /**
     * @return Shopware\Models\Property\Group
     */
    public function getPropertyGroupRepository(){
        if ($this->propertyGroupRepository === null){
            $this->propertyGroupRepository = $this->getManager()->getRepository('Shopware\Models\Property\Group');
        }

        return $this->propertyGroupRepository;
    }

    /**
     * @return Shopware\Models\Property\Option
     */
    public function getPropertyOptionRepository(){
        if ($this->propertyOptionRepository === null){
            $this->propertyOptionRepository = $this->getManager()->getRepository('Shopware\Models\Property\Option');
        }

        return $this->propertyOptionRepository;
    }

    /**
     * @return Shopware\Models\Property\Value
     */
    public function getPropertyValueRepository(){
        if ($this->propertyValueRepository === null){
            $this->propertyValueRepository = $this->getManager()->getRepository('Shopware\Models\Property\Value');
        }

        return $this->propertyValueRepository;
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

    public function getDb()
    {
        if ($this->db === null){
            $this->db = Shopware()->Db();
        }

        return $this->db;
    }

    public function getTranslationPropertyGroup($articleDetailIds)
    {
        $sql = "SELECT filter.name as baseName,
                ct.objectkey, ct.objectdata, ct.objectlanguage as propertyLanguageId
                FROM s_articles_details AS articleDetails

                INNER JOIN s_articles AS article
                ON article.id = articleDetails.articleID

                LEFT JOIN s_filter_articles AS fa
                ON fa.articleID = article.id

                LEFT JOIN s_filter_values AS fv
                ON fv.id = fa.valueID

                LEFT JOIN s_filter_relations AS fr
                ON fr.optionID = fv.optionID

                LEFT JOIN s_filter AS filter
                ON filter.id = fr.groupID

                LEFT JOIN s_core_translations AS ct
                ON ct.objectkey = filter.id

                WHERE articleDetails.id IN ($articleDetailIds) AND ct.objecttype = 'propertygroup'
                GROUP BY ct.id
                ";

        return $this->getDb()->query($sql)->fetchAll();
    }

    public function getTranslationPropertyOption($articleDetailIds)
    {
        $sql = "SELECT fo.name as baseName,
                ct.objectkey, ct.objectdata, ct.objectlanguage as propertyLanguageId
                FROM s_articles_details AS articleDetails

                INNER JOIN s_articles AS article
                ON article.id = articleDetails.articleID

                LEFT JOIN s_filter_articles AS fa
                ON fa.articleID = article.id

                LEFT JOIN s_filter_values AS fv
                ON fv.id = fa.valueID

                LEFT JOIN s_filter_options AS fo
                ON fo.id = fv.optionID

                LEFT JOIN s_core_translations AS ct
                ON ct.objectkey = fo.id

                WHERE articleDetails.id IN ($articleDetailIds) AND ct.objecttype = 'propertyoption'
                GROUP BY ct.id
                ";

        return $this->getDb()->query($sql)->fetchAll();
    }

    /**
     * Collects recursively category ids
     *
     * @param \Shopware\Models\Category\Category $categoryModel
     * @return
     */
    protected function collectCategoryIds($categoryModel)
    {
        $categoryId = $categoryModel->getId();
        $this->setCategoryIdCollection($categoryId);
        $categories = $categoryModel->getChildren();

        if (!$categories) {
            return;
        }

        foreach ($categories as $category) {
            $this->collectCategoryIds($category);
        }

        return;
    }

    public function getArticleBuilder($columns, $ids)
    {
        $articleBuilder = $this->getManager()->createQueryBuilder();
        $articleBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('Shopware\Models\Article\Detail', 'mv', \Doctrine\ORM\Query\Expr\Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
            ->leftJoin('variant.attribute', 'attribute')
            ->leftJoin('article.tax', 'articleTax')
            ->leftJoin('article.supplier', 'supplier')
            ->leftJoin('article.propertyGroup', 'filterGroup')
            ->leftJoin('article.esds', 'articleEsd')
            ->leftJoin('variant.unit', 'variantsUnit')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy("variant.kind");

        return $articleBuilder;
    }

    public function getPriceBuilder($columns, $ids)
    {
        $priceBuilder = $this->getManager()->createQueryBuilder();
        $priceBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('variant.prices', 'prices')
            ->leftJoin('prices.customerGroup', 'customerGroup')
            ->leftJoin('article.tax', 'articleTax')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $priceBuilder;
    }

    public function getImageBuilder($columns, $ids)
    {
        $imageBuilder = $this->getManager()->createQueryBuilder();
        $imageBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftjoin('article.images', 'images')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('images.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $imageBuilder;
    }

    public function getPropertyValueBuilder($columns, $ids)
    {
        $propertyValueBuilder = $this->getManager()->createQueryBuilder();
        $propertyValueBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftjoin('article.propertyGroup', 'propertyGroup')
            ->leftjoin('article.propertyValues', 'propertyValues')
            ->leftjoin('propertyValues.option', 'propertyOptions')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('propertyValues.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $propertyValueBuilder;
    }

    public function getConfiguratorBuilder($columns, $ids)
    {
        $configBuilder = $this->getManager()->createQueryBuilder();
        $configBuilder->select($columns)
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

        return $configBuilder;
    }

    public function getSimilarBuilder($columns, $ids)
    {
        $similarBuilder = $this->getManager()->createQueryBuilder();
        $similarBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftjoin('article.similar', 'similar')
            ->leftjoin('similar.details', 'similarDetail')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('similarDetail.kind = 1')
            ->andWhere('similar.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $similarBuilder;
    }

    public function getAccessoryBuilder($columns, $ids)
    {
        $accessoryBuilder = $this->getManager()->createQueryBuilder();
        $accessoryBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftjoin('article.related', 'accessory')
            ->leftjoin('accessory.details', 'accessoryDetail')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('accessoryDetail.kind = 1')
            ->andWhere('accessory.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $accessoryBuilder;
    }

    public function getCategoryBuilder($columns, $ids)
    {
        $categoryBuilder = $this->getManager()->createQueryBuilder();
        $categoryBuilder->select($columns)
            ->from('Shopware\Models\Article\Article', 'article')
            ->leftjoin('article.categories', 'categories')
            ->where('article.id IN (:ids)')
            ->andWhere('categories.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $categoryBuilder;
    }

    public function getElementBuilder()
    {
        $repository = $this->getManager()->getRepository('Shopware\Models\Article\Element');

        $builder = $repository->createQueryBuilder('attribute');
        $builder->andWhere('attribute.translatable = 1');
        $builder->orderBy('attribute.position');

        return $builder;
    }

}