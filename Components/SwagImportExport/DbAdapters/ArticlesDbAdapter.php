<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Join;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\ArticleWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\CategoryWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\ConfiguratorWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\PropertyWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\TranslationWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\PriceWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\RelationWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\ImageWriter;
use Shopware\Components\SwagImportExport\DbAdapters\Results\ArticleWriterResult;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\DbAdapterHelper;
use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
use Shopware\Components\SwagImportExport\Utils\SwagVersionHelper;
use Shopware\Models\Article\Element;
use Shopware\Models\Attribute\Configuration;

/**
 * Class ArticlesDbAdapter
 *
 * @package Shopware\Components\SwagImportExport\DbAdapters
 */
class ArticlesDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql $db
     */
    protected $db;

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
     * @var string
     */
    protected $logState;

    /**
     * @var array
     */
    protected $tempData;

    /**
     * @var MediaServiceInterface
     */
    private $mediaService;

    /**
     * @var array
     */
    protected $defaultValues = [];

    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * @var SnippetsHelper
     */
    private $snippetHelper;

    /**
     * @var DbAdapterHelper
     */
    private $dbAdapterHelper;

    public function __construct()
    {
        $this->db = Shopware()->Container()->get('db');
        $this->modelManager = Shopware()->Container()->get('models');
        $this->mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $this->config = Shopware()->Container()->get('config');
        $this->eventManager = Shopware()->Container()->get('events');
        $this->snippetHelper = new SnippetsHelper();
        $this->dbAdapterHelper = new DbAdapterHelper();
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $builder = $this->modelManager->createQueryBuilder();

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
            $category = $this->modelManager->find('Shopware\Models\Category\Category', $filter['categories'][0]);

            $this->collectCategoryIds($category);
            $categories = $this->getCategoryIdCollection();

            $categoriesBuilder = $this->modelManager->createQueryBuilder();
            $categoriesBuilder->select('article.id')
                ->from('Shopware\Models\Article\Article', 'article')
                ->leftJoin('article.categories', 'categories')
                ->where('categories.id IN (:cids)')
                ->setParameter('cids', $categories)
                ->groupBy('article.id');

            $articleIds = array_map(
                function ($item) {
                    return $item['id'];
                },
                $categoriesBuilder->getQuery()->getResult()
            );

            $builder->join('detail.article', 'article')
                ->andWhere('article.id IN (:ids)')
                ->setParameter('ids', $articleIds);
        }

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $records = $builder->getQuery()->getResult();

        $result = [];
        if ($records) {
            $result = array_column($records, 'id');
        }

        return $result;
    }

    /**
     * @param $ids
     * @param $columns
     * @return mixed
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (!$ids && empty($ids)) {
            $message = $this->snippetHelper->getNamespace()
                ->get('adapters/articles_no_ids', 'Can not read articles without ids.');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = $this->snippetHelper->getNamespace()
                ->get('adapters/articles_no_column_names', 'Can not read articles without column names.');
            throw new \Exception($message);
        }

        //articles
        $articleBuilder = $this->getArticleBuilder($columns['article'], $ids);

        $articles = $articleBuilder->getQuery()->getResult();

        $result['article'] = $this->dbAdapterHelper->decodeHtmlEntities($articles);

        //prices
        $columns['price'] = array_merge(
            $columns['price'],
            ['customerGroup.taxInput as taxInput', 'articleTax.tax as tax']
        );

        $priceBuilder = $this->getPriceBuilder($columns['price'], $ids);

        $result['price'] = $priceBuilder->getQuery()->getResult();

        if ($result['purchasePrice']) {
            $result['purchasePrice'] = round($result['purchasePrice'], 2);
        }

        foreach ($result['price'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = round($record['price'] * (100 + $record['tax']) / 100, 2);
                $record['pseudoPrice'] = round($record['pseudoPrice'] * (100 + $record['tax']) / 100, 2);
            } else {
                $record['price'] = round($record['price'], 2);
                $record['pseudoPrice'] = round($record['pseudoPrice'], 2);
            }

            if (!$record['inStock']) {
                $record['inStock'] = '0';
            }
        }

        //images
        $imageBuilder = $this->getImageBuilder($columns['image'], $ids);
        $tempImageResult = $imageBuilder->getQuery()->getResult();
        foreach ($tempImageResult as &$tempImage) {
            $tempImage['imageUrl'] = $this->mediaService->getUrl($tempImage['imageUrl']);
        }
        $result['image'] = $tempImageResult;

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
        $result['category'] = $this->prepareCategoryExport($ids, $columns['category']);

        $result['translation'] = $this->prepareTranslationExport($ids);

        return $result;
    }

    /**
     * @param $ids
     * @param $categoryColumns
     * @return array
     */
    public function prepareCategoryExport($ids, $categoryColumns)
    {
        $mappedArticleIds = $this->getArticleIdsByDetailIds($ids);

        $categoryBuilder = $this->getCategoryBuilder($categoryColumns, $mappedArticleIds);
        $articleCategories = $categoryBuilder->getQuery()->getResult();

        $categoryMapper = $this->getAssignedCategoryNames($articleCategories);

        //convert path
        foreach ($articleCategories as &$pathIds) {
            $pathIds['categoryPath'] = $this->generatePath($pathIds, $categoryMapper);
        }

        return $articleCategories;
    }

    /**
     * Returns article ids
     *
     * @param $detailIds
     * @return array
     */
    protected function getArticleIdsByDetailIds($detailIds)
    {
        $articleIds = $this->modelManager->createQueryBuilder()
            ->select('article.id')
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $detailIds)
            ->groupBy('article.id');

        $mappedArticleIds = array_map(
            function ($item) {
                return $item['id'];
            },
            $articleIds->getQuery()->getResult()
        );

        return $mappedArticleIds;
    }

    /**
     * Collects and creates a helper mapper for category path
     *
     * @param array $categories
     * @return array
     */
    protected function getAssignedCategoryNames($categories)
    {
        $categoryIds = [];
        foreach ($categories as $category) {
            if (!empty($category['categoryId'])) {
                $categoryIds[] = (string) $category['categoryId'];
            }

            if (!empty($category['categoryPath'])) {
                $catPath = explode('|', $category['categoryPath']);
                $categoryIds = array_merge($categoryIds, $catPath);
            }
        }

        //only unique ids
        $categoryIds = array_unique($categoryIds);

        //removes empty value
        $categoryIds = array_filter($categoryIds);

        $categoriesNames = $this->modelManager->createQueryBuilder()
            ->select(['category.id, category.name'])
            ->from('Shopware\Models\Category\Category', 'category')
            ->where('category.id IN (:ids)')
            ->setParameter('ids', $categoryIds)
            ->getQuery()->getResult();

        $names = [];
        foreach ($categoriesNames as $name) {
            $names[$name['id']] = $name['name'];
        }

        return $names;
    }

    /**
     * @param array $category contains category data
     * @param array $mapper contains categories' names
     * @return string converted path
     */
    protected function generatePath($category, $mapper)
    {
        $ids = [];
        if (!empty($category['categoryPath'])) {
            foreach (explode('|', $category['categoryPath']) as $id) {
                $ids[] = $mapper[$id];
            }
        }
        krsort($ids);

        if (!empty($category['categoryId'])) {
            $ids[] = $mapper[$category['categoryId']];
        }

        $ids = array_filter($ids);

        $path = implode('->', $ids);

        return $path;
    }

    /**
     * @param $ids
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function prepareTranslationExport($ids)
    {
        $articleDetailIds = implode(',', $ids);

        $sql = "SELECT variant.articleID as articleId, variant.id as variantId, variant.kind, ct.objectdata, ct.objectlanguage as languageId
                FROM s_articles_details AS variant
                LEFT JOIN s_core_translations AS ct ON variant.id = ct.objectkey AND objecttype = 'variant'
                WHERE variant.id IN ($articleDetailIds)
                ORDER BY languageId ASC
                ";
        $translations = $this->db->query($sql)->fetchAll();

        //all translation fields that can be translated for an article
        $translationFields = $this->getTranslationFields();
        $rows = [];
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

            $objectData = unserialize($record['objectdata']);
            if (!empty($objectData)) {
                foreach ($objectData as $key => $value) {
                    if (isset($translationFields[$key])) {
                        $rows[$variantId][$languageId][$translationFields[$key]] = $value;
                    }
                }
            }
        }

        $shops = $this->getShops();
        unset($shops[0]); //removes default language

        $result = [];
        foreach ($rows as $vId => $row) {
            foreach ($shops as $shop) {
                $shopId = $shop->getId();
                if (isset($row[$shopId])) {
                    $result[] = $row[$shopId];
                } else {
                    $result[] = [
                        'articleId' => $row['helper']['articleId'],
                        'variantId' => $vId,
                        'languageId' => (string) $shopId,
                        'variantKind' => $row['helper']['variantKind'],
                    ];
                }
            }
        }

        //Sets missing translation fields with empty string
        foreach ($result as &$row) {
            foreach ($translationFields as $field) {
                if (!isset($row[$field])) {
                    $row[$field] = '';
                }
            }
        }

        $sql = "SELECT variant.articleID as articleId, ct.objectdata, ct.objectlanguage as languageId
                FROM s_articles_details AS variant
                LEFT JOIN s_core_translations AS ct ON variant.articleID = ct.objectkey
                WHERE variant.id IN ($articleDetailIds) AND objecttype = 'article'
                GROUP BY ct.id
                ";
        $articles = $this->db->query($sql)->fetchAll();

        foreach ($result as $index => $translation) {
            foreach ($articles as $article) {
                //the translation for the main variant is coming
                //from article translations
                if ($translation['variantKind'] == 1
                    && $translation['articleId'] === $article['articleId']
                    && $translation['languageId'] === $article['languageId']
                ) {
                    $serializeData = unserialize($article['objectdata']);
                    foreach ($translationFields as $key => $field) {
                        $result[$index][$field] = $serializeData[$key];
                    }
                } elseif ($translation['articleId'] === $article['articleId']
                    && $translation['languageId'] === $article['languageId']
                ) {
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

    /**
     * Returns all fields that can be translated.
     *
     * @return array
     */
    private function getTranslationFields()
    {
        $translationFields = [
            'metaTitle' => 'metaTitle',
            'txtArtikel' => 'name',
            'txtkeywords' => 'keywords',
            'txtpackunit' => 'packUnit',
            'txtzusatztxt' => 'additionalText',
            'txtshortdescription' => 'description',
            'txtlangbeschreibung' => 'descriptionLong'
        ];

        if (!SwagVersionHelper::isDeprecated('5.3.0')) {
            $legacyAttributes = $this->getLegacyTranslationAttr();
            foreach ($legacyAttributes as $attr) {
                $translationFields[$attr['name']] = $attr['name'];
            }
        }

        $attributes = $this->getTranslatableAttributes();
        foreach ($attributes as $attribute) {
            $translationFields['__attribute_' . $attribute['columnName']] = $attribute['columnName'];
        }

        return $translationFields;
    }

    /**
     * @return \Shopware\Models\Shop\Shop[]
     */
    public function getShops()
    {
        return $this->modelManager->getRepository('Shopware\Models\Shop\Shop')->findAll();
    }

    /**
     * Returns default columns
     *
     * @return array
     */
    public function getDefaultColumns()
    {
        $otherColumns = [
            'variantsUnit.unit as unit',
            'articleEsd.file as esd',
        ];

        $columns['article'] = array_merge(
            $this->getArticleColumns(),
            $otherColumns
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

    /**
     * Return list with default values for fields which are empty or don't exists
     *
     * @return array
     */
    private function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * Set default values for fields which are empty or don't exists
     *
     * @param array $values default values for nodes
     */
    public function setDefaultValues($values)
    {
        $this->defaultValues = $values;
    }

    /**
     * @param array $records
     * @throws \Exception
     */
    private function performImport($records)
    {
        $articleWriter = new ArticleWriter();
        $pricesWriter = new PriceWriter();
        $categoryWriter = new CategoryWriter();
        $configuratorWriter = ConfiguratorWriter::createFromGlobalSingleton();
        $translationWriter = new TranslationWriter();
        $propertyWriter = PropertyWriter::createFromGlobalSingleton();
        $relationWriter = new RelationWriter($this);
        $imageWriter = new ImageWriter($this);

        $defaultValues = $this->getDefaultValues();

        foreach ($records['article'] as $index => $article) {
            try {
                $this->modelManager->getConnection()->beginTransaction();

                $articleWriterResult = $articleWriter->write($article, $defaultValues);

                $processedFlag = isset($article['processed']) && $article['processed'] == 1;

                /**
                 * Only processed data will be imported
                 */
                if (!$processedFlag) {
                    $pricesWriter->write(
                        $articleWriterResult->getArticleId(),
                        $articleWriterResult->getDetailId(),
                        array_filter(
                            $records['price'],
                            function ($price) use ($index) {
                                return $price['parentIndexElement'] == $index;
                            }
                        )
                    );

                    $categoryWriter->write(
                        $articleWriterResult->getArticleId(),
                        array_filter(
                            $records['category'],
                            function ($category) use ($index) {
                                return $category['parentIndexElement'] == $index && ($category['categoryId'] || $category['categoryPath']);
                            }
                        )
                    );

                    $configuratorWriter->writeOrUpdateConfiguratorSet(
                        $articleWriterResult,
                        array_filter(
                            $records['configurator'],
                            function ($configurator) use ($index) {
                                return $configurator['parentIndexElement'] == $index;
                            }
                        )
                    );

                    $propertyWriter->writeUpdateCreatePropertyGroupsFilterAndValues(
                        $articleWriterResult->getArticleId(),
                        $article['orderNumber'],
                        $this->filterPropertyValues($records, $index, $articleWriterResult)
                    );

                    $translationWriter->write(
                        $articleWriterResult->getArticleId(),
                        $articleWriterResult->getDetailId(),
                        $articleWriterResult->getMainDetailId(),
                        array_filter(
                            $records['translation'],
                            function ($translation) use ($index) {
                                return $translation['parentIndexElement'] == $index;
                            }
                        )
                    );
                }

                /**
                 * Processed and unprocessed data will be imported
                 */
                if ($processedFlag) {
                    $article['mainNumber'] = $article['orderNumber'];
                }

                $relationWriter->write(
                    $articleWriterResult->getArticleId(),
                    $article['mainNumber'],
                    array_filter(
                        $records['accessory'],
                        function ($accessory) use ($index, $articleWriterResult) {
                            return $accessory['parentIndexElement'] == $index && $articleWriterResult->getMainDetailId() == $articleWriterResult->getDetailId();
                        }
                    ),
                    'accessory',
                    $processedFlag
                );

                $relationWriter->write(
                    $articleWriterResult->getArticleId(),
                    $article['mainNumber'],
                    array_filter(
                        $records['similar'],
                        function ($similar) use ($index, $articleWriterResult) {
                            return $similar['parentIndexElement'] == $index && $articleWriterResult->getMainDetailId() == $articleWriterResult->getDetailId();
                        }
                    ),
                    'similar',
                    $processedFlag
                );

                $imageWriter->write(
                    $articleWriterResult->getArticleId(),
                    $article['mainNumber'],
                    array_filter(
                        $records['image'],
                        function ($image) use ($index) {
                            return $image['parentIndexElement'] == $index;
                        }
                    )
                );

                $this->modelManager->getConnection()->commit();
            } catch (AdapterException $e) {
                $this->modelManager->getConnection()->rollBack();
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
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
        //articles
        if (empty($records['article'])) {
            $message = $this->snippetHelper->getNamespace()->get(
                'adapters/articles/no_records',
                'No article records were found.'
            );
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $this->performImport($records);
    }

    /**
     * @param int $id
     * @return \Shopware\Models\Shop\Shop $shop
     * @throws AdapterException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getShop($id)
    {
        $shop = $this->modelManager->find('Shopware\Models\Shop\Shop', $id);
        if (!$shop) {
            $message = $this->snippetHelper->getNamespace()->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
            throw new AdapterException(sprintf($message, $id));
        }

        return $shop;
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return [
            ['id' => 'article', 'name' => 'article'],
            ['id' => 'price', 'name' => 'price'],
            ['id' => 'image', 'name' => 'image'],
            ['id' => 'propertyValue', 'name' => 'propertyValue'],
            ['id' => 'similar', 'name' => 'similar'],
            ['id' => 'accessory', 'name' => 'accessory'],
            ['id' => 'configurator', 'name' => 'configurator'],
            ['id' => 'category', 'name' => 'category'],
            ['id' => 'translation', 'name' => 'translation']
        ];
    }

    /**
     * @return array
     */
    public function getArticleColumns()
    {
        return array_merge($this->getArticleVariantColumns(), $this->getVariantColumns());
    }

    /**
     * @return array
     */
    public function getArticleVariantColumns()
    {
        return [
            'article.id as articleId',
            'article.name as name',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            "DATE_FORMAT(article.added, '%Y-%m-%d') as date",
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
        ];
    }

    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getArticleAttributes()
    {
        $stmt = $this->db->query("SHOW COLUMNS FROM `s_articles_attributes`");
        $columns = $stmt->fetchAll();

        $attributes = $this->filterAttributeColumns($columns);

        $attributesSelect = [];
        if ($attributes) {
            $prefix = 'attribute';
            foreach ($attributes as $attribute) {
                $attr = $this->underscoreToCamelCase($attribute);

                $attributesSelect[] = sprintf('%s.%s as attribute%s', $prefix, $attr, ucwords($attr));
            }
        }

        return $attributesSelect;
    }

    /**
     * @param $section
     * @return bool
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
     * @param $section
     * @return array
     */
    public function getParentKeys($section)
    {
        switch ($section) {
            case 'article':
                return [
                    'article.id as articleId',
                    'variant.id as variantId',
                    'variant.number as orderNumber',
                ];
            case 'price':
                return [
                    'prices.articleDetailsId as variantId',
                ];
            case 'propertyValue':
                return [
                    'article.id as articleId',
                ];
            case 'similar':
                return [
                    'article.id as articleId',
                ];
            case 'accessory':
                return [
                    'article.id as articleId',
                ];
            case 'image':
                return [
                    'article.id as articleId',
                ];
            case 'configurator':
                return [
                    'variant.id as variantId',
                ];
            case 'category':
                return [
                    'article.id as articleId',
                ];
            case 'translation':
                return [
                    'variant.id as variantId',
                ];
        }
    }

    /**
     * @return array
     */
    public function getVariantColumns()
    {
        $columns = [
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
            'variant.purchasePrice as purchasePrice'
        ];

        // Attributes
        $attributesSelect = $this->getArticleAttributes();

        if ($attributesSelect && !empty($attributesSelect)) {
            $columns = array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @return array
     */
    public function getPriceColumns()
    {
        return [
            'prices.articleDetailsId as variantId',
            'prices.articleId as articleId',
            'prices.price as price',
            'prices.pseudoPrice as pseudoPrice',
            'prices.customerGroupKey as priceGroup',
        ];
    }

    /**
     * @return array
     */
    public function getImageColumns()
    {
        return [
            'images.id as id',
            'images.articleId as articleId',
            'images.articleDetailId as variantId',
            'images.path as path',
            "CONCAT('media/image/', images.path, '.', images.extension) as imageUrl",
            'images.main as main',
            'images.mediaId as mediaId',
            ' \'1\' as thumbnail'
        ];
    }

    /**
     * @return array
     */
    public function getPropertyValueColumns()
    {
        return [
            'article.id as articleId',
            'propertyGroup.name as propertyGroupName',
            'propertyValues.id as propertyValueId',
            'propertyValues.value as propertyValueName',
            'propertyValues.position as propertyValuePosition',
            'propertyOptions.name as propertyOptionName',
        ];
    }

    /**
     * @return array
     */
    public function getSimilarColumns()
    {
        return [
            'similar.id as similarId',
            'similarDetail.number as ordernumber',
            'article.id as articleId',
        ];
    }

    /**
     * @return array
     */
    public function getAccessoryColumns()
    {
        return [
            'accessory.id as accessoryId',
            'accessoryDetail.number as ordernumber',
            'article.id as articleId',
        ];
    }

    /**
     * @return array
     */
    public function getConfiguratorColumns()
    {
        return [
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
        ];
    }

    /**
     * @return array
     */
    public function getCategoryColumns()
    {
        return [
            'categories.id as categoryId',
            'categories.path as categoryPath',
            'article.id as articleId',
        ];
    }

    /**
     * @return array
     */
    public function getTranslationColumns()
    {
        $columns = [
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
        ];

        if (!SwagVersionHelper::isDeprecated('5.3.0')) {
            $legacyAttributes = $this->getLegacyTranslationAttr();

            if ($legacyAttributes) {
                foreach ($legacyAttributes as $attr) {
                    $columns[] = $attr['name'];
                }
            }
        }

        $attributes = $this->getTranslatableAttributes();

        if ($attributes) {
            foreach ($attributes as $attribute) {
                $columns[] = $attribute['columnName'];
            }
        }

        return $columns;
    }

    /**
     * @return mixed
     */
    public function getLegacyTranslationAttr()
    {
        $elementBuilder = $this->getElementBuilder();

        return $elementBuilder->getQuery()->getArrayResult();
    }

    /**
     * @return array
     */
    private function getTranslatableAttributes()
    {
        $repository = $this->modelManager->getRepository(Configuration::class);

        return $repository->createQueryBuilder('configuration')
            ->select('configuration.columnName')
            ->where('configuration.tableName = :tablename')
            ->andWhere('configuration.translatable = 1')
            ->setParameter('tablename', 's_articles_attributes')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array
     */
    public function getLogMessages()
    {
        return $this->logMessages;
    }

    /**
     * @param $logMessages
     */
    public function setLogMessages($logMessages)
    {
        $this->logMessages[] = $logMessages;
    }

    /**
     * @return string
     */
    public function getLogState()
    {
        return $this->logState;
    }

    /**
     * @param $logState
     */
    public function setLogState($logState)
    {
        $this->logState = $logState;
    }

    /**
     * @return array
     */
    public function getCategoryIdCollection()
    {
        return $this->categoryIdCollection;
    }

    /**
     * @param $categoryIdCollection
     */
    public function setCategoryIdCollection($categoryIdCollection)
    {
        $this->categoryIdCollection[] = $categoryIdCollection;
    }

    /**
     * @param $profileName
     * @param $type
     * @param $articleNumber
     * @param $data
     */
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

        $articleData = [
            'articleId' => $articleNumber,
            'mainNumber' => $articleNumber,
            'orderNumber' => $articleNumber,
            'processed' => 1
        ];

        $this->setUnprocessedData('articles', 'article', $articleData);
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @param $profileName
     * @param $type
     * @param $data
     */
    public function setUnprocessedData($profileName, $type, $data)
    {
        $this->unprocessedData[$profileName][$type][] = $data;
    }

    /**
     * @return array
     */
    public function getTempData()
    {
        return $this->tempData;
    }

    /**
     * @param $tempData
     */
    public function setTempData($tempData)
    {
        $this->tempData[$tempData] = $tempData;
    }

    /**
     * @param $articleDetailIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
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

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * @param $articleDetailIds
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
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

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Collects recursively category ids
     *
     * @param \Shopware\Models\Category\Category $categoryModel
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

    /**
     * @param $columns
     * @param $ids - s_articles_details.id
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getArticleBuilder($columns, $ids)
    {
        $articleBuilder = $this->modelManager->createQueryBuilder();
        $articleBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('Shopware\Models\Article\Detail', 'mv', Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
            ->leftJoin('variant.attribute', 'attribute')
            ->leftJoin('article.tax', 'articleTax')
            ->leftJoin('article.supplier', 'supplier')
            ->leftJoin('article.propertyGroup', 'filterGroup')
            ->leftJoin('article.esds', 'articleEsd')
            ->leftJoin('variant.unit', 'variantsUnit')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->orderBy("variant.kind");

        return $articleBuilder;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getPriceBuilder($columns, $ids)
    {
        $priceBuilder = $this->modelManager->createQueryBuilder();
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

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getImageBuilder($columns, $ids)
    {
        $imageBuilder = $this->modelManager->createQueryBuilder();
        $imageBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('article.images', 'images')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('images.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $imageBuilder;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getPropertyValueBuilder($columns, $ids)
    {
        $propertyValueBuilder = $this->modelManager->createQueryBuilder();
        $propertyValueBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('article.propertyGroup', 'propertyGroup')
            ->leftJoin('article.propertyValues', 'propertyValues')
            ->leftJoin('propertyValues.option', 'propertyOptions')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('propertyValues.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $propertyValueBuilder;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getConfiguratorBuilder($columns, $ids)
    {
        $configBuilder = $this->modelManager->createQueryBuilder();
        $configBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('variant.configuratorOptions', 'configuratorOptions')
            ->leftJoin('configuratorOptions.group', 'configuratorGroup')
            ->leftJoin('article.configuratorSet', 'configuratorSet')
            ->where('variant.id IN (:ids)')
            ->andWhere('configuratorOptions.id IS NOT NULL')
            ->andWhere('configuratorGroup.id IS NOT NULL')
            ->andWhere('configuratorSet.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $configBuilder;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getSimilarBuilder($columns, $ids)
    {
        $similarBuilder = $this->modelManager->createQueryBuilder();
        $similarBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('article.similar', 'similar')
            ->leftJoin('similar.details', 'similarDetail')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('similarDetail.kind = 1')
            ->andWhere('similar.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $similarBuilder;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getAccessoryBuilder($columns, $ids)
    {
        $accessoryBuilder = $this->modelManager->createQueryBuilder();
        $accessoryBuilder->select($columns)
            ->from('Shopware\Models\Article\Detail', 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('article.related', 'accessory')
            ->leftJoin('accessory.details', 'accessoryDetail')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('accessoryDetail.kind = 1')
            ->andWhere('accessory.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $accessoryBuilder;
    }

    /**
     * @param $columns
     * @param $ids
     * @return \Doctrine\ORM\QueryBuilder|\Shopware\Components\Model\QueryBuilder
     */
    public function getCategoryBuilder($columns, $ids)
    {
        $categoryBuilder = $this->modelManager->createQueryBuilder();
        $categoryBuilder->select($columns)
            ->from('Shopware\Models\Article\Article', 'article')
            ->leftJoin('article.categories', 'categories')
            ->where('article.id IN (:ids)')
            ->andWhere('categories.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $categoryBuilder;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getElementBuilder()
    {
        $repository = $this->modelManager->getRepository(Element::class);

        $builder = $repository->createQueryBuilder('attribute');
        $builder->andWhere('attribute.translatable = 1');
        $builder->orderBy('attribute.position');

        return $builder;
    }

    /**
     * @param array $records
     * @param int $index
     * @param ArticleWriterResult $articleWriterResult
     * @return array
     */
    private function filterPropertyValues($records, $index, ArticleWriterResult $articleWriterResult)
    {
        return array_filter(
            $records['propertyValue'],
            function ($property) use ($index, $articleWriterResult) {
                return $property['parentIndexElement'] == $index && $articleWriterResult->getMainDetailId() == $articleWriterResult->getDetailId();
            }
        );
    }

    /**
     * @example: underscore_string will result in underscoreString
     *
     * @param string $underscoreString
     * @return string
     */
    private function underscoreToCamelCase($underscoreString)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $underscoreString))));
    }

    /**
     * @param array $columns
     * @return array
     */
    private function filterAttributeColumns(array $columns)
    {
        $attributes = [];
        foreach ($columns as $column) {
            if ($column['Field'] !== 'id'
                && $column['Field'] !== 'articleID'
                && $column['Field'] !== 'articledetailsID'
            ) {
                $attributes[] = $column['Field'];
            }
        }
        return $attributes;
    }
}
