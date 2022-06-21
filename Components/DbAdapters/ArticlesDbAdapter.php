<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\ProductNumberSearchInterface;
use Shopware\Bundle\SearchBundle\ProductNumberSearchResult;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\BaseProduct;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\ProductStream\Repository;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Configuration;
use Shopware\Models\Category\Category;
use Shopware\Models\Shop\Repository as ShopRepository;
use Shopware\Models\Shop\Shop;
use SwagImportExport\Components\DbAdapters\Articles\ArticleWriter;
use SwagImportExport\Components\DbAdapters\Articles\CategoryWriter;
use SwagImportExport\Components\DbAdapters\Articles\ConfiguratorWriter;
use SwagImportExport\Components\DbAdapters\Articles\ImageWriter;
use SwagImportExport\Components\DbAdapters\Articles\PriceWriter;
use SwagImportExport\Components\DbAdapters\Articles\PropertyWriter;
use SwagImportExport\Components\DbAdapters\Articles\RelationWriter;
use SwagImportExport\Components\DbAdapters\Articles\TranslationWriter;
use SwagImportExport\Components\DbAdapters\Results\ArticleWriterResult;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Service\UnderscoreToCamelCaseServiceInterface;
use SwagImportExport\Components\Utils\DbAdapterHelper;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Utils\SwagVersionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ArticlesDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    public const VARIANTS_FILTER_KEY = 'variants';
    public const CATEGORIES_FILTER_KEY = 'categories';
    public const PRODUCT_STREAM_ID_FILTER_KEY = 'productStreamId';
    private const MAIN_KIND = 1;

    protected ModelManager $modelManager;

    protected Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    protected array $unprocessedData;

    protected array $logMessages = [];

    protected ?string $logState = null;

    /**
     * @var array<string, string>
     */
    protected ?array $tempData = null;

    protected array $defaultValues = [];

    protected ContainerInterface $container;

    private MediaServiceInterface $mediaService;

    private \Shopware_Components_Config $config;

    private ContainerAwareEventManager $eventManager;

    private UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService;

    private ContextServiceInterface $contextService;

    private Repository $streamRepo;

    private ProductNumberSearchInterface $productNumberSearch;

    private ShopRepository $shopRepository;

    private ArticleWriter $articleWriter;

    private PriceWriter $priceWriter;

    private CategoryWriter $categoryWriter;

    private ConfiguratorWriter $configuratorWriter;

    private TranslationWriter $translationWriter;

    private PropertyWriter $propertyWriter;

    private RelationWriter $relationWriter;

    private ImageWriter $imageWriter;

    public function __construct(
        ContainerInterface $container,
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ModelManager $modelManager,
        MediaServiceInterface $mediaService,
        \Shopware_Components_Config $config,
        ContainerAwareEventManager $eventManager,
        UnderscoreToCamelCaseServiceInterface $underscoreToCamelCaseService,
        ContextServiceInterface $contextService,
        Repository $streamRepo,
        ProductNumberSearchInterface $productNumberSearch,
        ArticleWriter $articleWriter,
        PriceWriter $priceWriter,
        CategoryWriter $categoryWriter,
        ConfiguratorWriter $configuratorWriter,
        TranslationWriter $translationWriter,
        PropertyWriter $propertyWriter,
        RelationWriter $relationWriter,
        ImageWriter $imageWriter
    ) {
        $this->container = $container;
        $this->db = $db;
        $this->modelManager = $modelManager;
        $this->mediaService = $mediaService;
        $this->config = $config;
        $this->eventManager = $eventManager;
        $this->underscoreToCamelCaseService = $underscoreToCamelCaseService;
        $this->contextService = $contextService;
        $this->streamRepo = $streamRepo;
        $this->productNumberSearch = $productNumberSearch;
        $this->shopRepository = $this->modelManager->getRepository(Shop::class);
        $this->articleWriter = $articleWriter;
        $this->priceWriter = $priceWriter;
        $this->categoryWriter = $categoryWriter;
        $this->configuratorWriter = $configuratorWriter;
        $this->translationWriter = $translationWriter;
        $this->propertyWriter = $propertyWriter;
        $this->relationWriter = $relationWriter;
        $this->imageWriter = $imageWriter;
    }

    /**
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $builder = $this->modelManager->createQueryBuilder();

        $builder->select('detail.id');

        $builder->from(Detail::class, 'detail')
            ->orderBy('detail.articleId', 'ASC')
            ->orderBy('detail.kind', 'ASC');

        if (!$filter[self::VARIANTS_FILTER_KEY]) {
            $builder->andWhere('detail.kind = ' . self::MAIN_KIND);
        }

        if ($filter[self::CATEGORIES_FILTER_KEY]) {
            /** @var Category $category */
            $category = $this->modelManager->find(Category::class, $filter['categories'][0]);

            $categories = [];
            $this->collectCategoryIds($category, $categories);

            $categoriesBuilder = $this->modelManager->createQueryBuilder();
            $categoriesBuilder->select('article.id')
                ->from(Article::class, 'article')
                ->leftJoin('article.categories', 'categories')
                ->where('categories.id IN (:cids)')
                ->setParameter('cids', $categories)
                ->groupBy('article.id');

            $articleIds = \array_map(
                function ($item) {
                    return $item['id'];
                },
                $categoriesBuilder->getQuery()->getResult()
            );

            $builder->join('detail.article', 'article')
                ->andWhere('article.id IN (:ids)')
                ->setParameter('ids', $articleIds);
        } elseif ($filter[self::PRODUCT_STREAM_ID_FILTER_KEY]) {
            $productStreamId = $filter[self::PRODUCT_STREAM_ID_FILTER_KEY][0];

            $shop = $this->shopRepository->getActiveDefault();
            $context = $this->contextService->createShopContext($shop->getId());
            $criteria = new Criteria();
            $this->streamRepo->prepareCriteria($criteria, $productStreamId);

            /** @var ProductNumberSearchResult $products */
            $products = $this->productNumberSearch->search($criteria, $context);
            if (empty($products->getProducts())) {
                return [];
            }

            if ($filter[self::VARIANTS_FILTER_KEY]) {
                $productIds = array_values(array_map(static function (BaseProduct $product) {
                    return $product->getId();
                }, $products->getProducts()));

                $builder
                    ->join('detail.article', 'article')
                    ->andWhere('article.id IN (:ids)')
                    ->setParameter('ids', $productIds);
            } else {
                $productNumbers = \array_keys($products->getProducts());

                $builder
                    ->andWhere('detail.number IN(:productNumbers)')
                    ->setParameter('productNumbers', $productNumbers);
            }
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
            $result = \array_column($records, 'id');
        }

        return $result;
    }

    /**
     * @param array<string, array<string>> $columns
     */
    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_ids', 'Can not read articles without ids.');
            throw new \RuntimeException($message);
        }

        if (empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_column_names', 'Can not read articles without column names.');
            throw new \RuntimeException($message);
        }

        $products = $this->getArticleBuilder($columns['article'], $ids)->getQuery()->getResult();

        $result['article'] = DbAdapterHelper::decodeHtmlEntities($products);

        array_push($columns['price'], 'customerGroup.taxInput as taxInput', 'articleTax.tax as tax');

        $result['price'] = $this->getPriceBuilder($columns['price'], $ids)->getQuery()->getResult();

        foreach ($result['price'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = \round($record['price'] * (100 + $record['tax']) / 100, 2);
                $record['pseudoPrice'] = \round($record['pseudoPrice'] * (100 + $record['tax']) / 100, 2);
                if (SwagVersionHelper::isShopware578()) {
                    $record['regulationPrice'] = \round($record['regulationPrice'] * (100 + $record['tax']) / 100, 2);
                }
            } else {
                $record['price'] = \round($record['price'], 2);
                $record['pseudoPrice'] = \round($record['pseudoPrice'], 2);
                if (SwagVersionHelper::isShopware578() && isset($record['regulationPrice'])) {
                    $record['regulationPrice'] = round($record['regulationPrice'], 2);
                }
            }

            if (!$record['inStock']) {
                $record['inStock'] = '0';
            }
        }
        unset($record);

        // images
        $imageBuilder = $this->getImageBuilder($columns['image'], $ids);
        $tempImageResult = $imageBuilder->getQuery()->getResult();
        foreach ($tempImageResult as &$tempImage) {
            $tempImage['imageUrl'] = $this->mediaService->getUrl($tempImage['imageUrl']);
        }
        unset($tempImage);
        $result['image'] = $tempImageResult;

        // filter values
        $propertyValuesBuilder = $this->getPropertyValueBuilder($columns['propertyValues'], $ids);
        $result['propertyValue'] = $propertyValuesBuilder->getQuery()->getResult();

        // configurator
        $configBuilder = $this->getConfiguratorBuilder($columns['configurator'], $ids);
        $result['configurator'] = $configBuilder->getQuery()->getResult();

        // similar
        $similarsBuilder = $this->getSimilarBuilder($columns['similar'], $ids);
        $result['similar'] = $similarsBuilder->getQuery()->getResult();

        // accessories
        $accessoryBuilder = $this->getAccessoryBuilder($columns['accessory'], $ids);
        $result['accessory'] = $accessoryBuilder->getQuery()->getResult();

        // categories
        $result['category'] = $this->prepareCategoryExport($ids, $columns['category']);

        $result['translation'] = $this->prepareTranslationExport($ids);

        return $result;
    }

    /**
     * @param array<int>    $ids
     * @param array<string> $categoryColumns
     *
     * @return array<string, mixed>
     */
    public function prepareCategoryExport(array $ids, array $categoryColumns): array
    {
        $mappedArticleIds = $this->getArticleIdsByDetailIds($ids);

        $categoryBuilder = $this->getCategoryBuilder($categoryColumns, $mappedArticleIds);
        $articleCategories = $categoryBuilder->getQuery()->getResult();

        $categoryMapper = $this->getAssignedCategoryNames($articleCategories);

        // convert path
        foreach ($articleCategories as &$pathIds) {
            $pathIds['categoryPath'] = $this->generatePath($pathIds, $categoryMapper);
        }

        return $articleCategories;
    }

    /**
     * @param array<int> $ids
     *
     * @return array<int, array<string, mixed>>
     */
    public function prepareTranslationExport(array $ids): array
    {
        $productDetailIds = \implode(',', $ids);

        $sql = "SELECT variant.articleID as articleId, variant.id as variantId, variant.kind, ct.objectdata, ct.objectlanguage as languageId
                FROM s_articles_details AS variant
                LEFT JOIN s_core_translations AS ct ON variant.id = ct.objectkey AND objecttype = 'variant'
                WHERE variant.id IN ($productDetailIds)
                ORDER BY languageId ASC
                ";
        $translations = $this->db->query($sql)->fetchAll();

        // all translation fields that can be translated for an article
        $translationFields = $this->getTranslationFields();
        $rows = [];
        foreach ($translations as $index => $record) {
            $productId = $record['articleId'];
            $variantId = $record['variantId'];
            $languageId = $record['languageId'];
            $kind = $record['kind'];
            $rows[$variantId]['helper']['articleId'] = $productId;
            $rows[$variantId]['helper']['variantKind'] = $kind;
            $rows[$variantId][$languageId]['articleId'] = $productId;
            $rows[$variantId][$languageId]['variantId'] = $variantId;
            $rows[$variantId][$languageId]['languageId'] = $languageId;
            $rows[$variantId][$languageId]['variantKind'] = $kind;

            $objectData = $record['objectdata'] ? \unserialize($record['objectdata']) : null;
            if (!empty($objectData)) {
                foreach ($objectData as $key => $value) {
                    if (isset($translationFields[$key])) {
                        $rows[$variantId][$languageId][$translationFields[$key]] = $value;
                    }
                }
            }
        }

        $shops = $this->getShops();
        unset($shops[0]); // removes default language

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

        // Sets missing translation fields with empty string
        foreach ($result as &$resultRow) {
            foreach ($translationFields as $field) {
                if (!isset($resultRow[$field])) {
                    $resultRow[$field] = '';
                }
            }
        }
        unset($resultRow);

        $sql = "SELECT variant.articleID as articleId, ct.objectdata, ct.objectlanguage as languageId
                FROM s_articles_details AS variant
                LEFT JOIN s_core_translations AS ct ON variant.articleID = ct.objectkey
                WHERE variant.id IN ($productDetailIds) AND objecttype = 'article'
                GROUP BY ct.id
                ";
        $products = $this->db->query($sql)->fetchAll();

        $mappedProducts = [];
        foreach ($products as $product) {
            $mappedProducts[$product['articleId']][$product['languageId']] = $product;
        }

        foreach ($result as $index => $translation) {
            $matchedProductTranslation = $mappedProducts[$translation['articleId']][$translation['languageId']];
            if ((int) $translation['variantKind'] === 1 && $matchedProductTranslation) {
                $serializeData = \unserialize($matchedProductTranslation['objectdata']);
                foreach ($translationFields as $key => $field) {
                    $result[$index][$field] = $serializeData[$key];
                }

                continue;
            }

            if (!\is_string($matchedProductTranslation['objectdata'])) {
                continue;
            }

            $data = \unserialize($matchedProductTranslation['objectdata']);
            $result[$index]['name'] = $data['txtArtikel'];
            $result[$index]['description'] = $data['txtshortdescription'];
            $result[$index]['descriptionLong'] = $data['txtlangbeschreibung'];
            $result[$index]['metaTitle'] = $data['metaTitle'];
            $result[$index]['keywords'] = $data['txtkeywords'];
            $result[$index]['shippingtime'] = $data['txtshippingtime'];
        }

        return $result;
    }

    /**
     * @return \Shopware\Models\Shop\Shop[]
     */
    public function getShops(): array
    {
        return $this->modelManager->getRepository(Shop::class)->findAll();
    }

    /**
     * Returns default columns
     *
     * @return array<string, array<string>>
     */
    public function getDefaultColumns(): array
    {
        $otherColumns = [
            'variantsUnit.unit as unit',
            'articleEsd.file as esd',
        ];

        $columns['article'] = \array_merge(
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
     * Set default values for fields which are empty or don't exists
     *
     * @param array<string, mixed> $values default values for nodes
     */
    public function setDefaultValues(array $values): void
    {
        $this->defaultValues = $values;
    }

    /**
     * Writes articles into the database
     *
     * @param array<string, mixed> $records
     *
     * @throws \RuntimeException
     */
    public function write(array $records): void
    {
        // articles
        if (empty($records['article'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/no_records',
                'No article records were found.'
            );
            throw new \RuntimeException($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $this->performImport($records);
    }

    /**
     * @throws AdapterException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return \Shopware\Models\Shop\Shop $shop
     */
    public function getShop(int $id): Shop
    {
        $shop = $this->modelManager->find(Shop::class, $id);
        if (!$shop) {
            $message = SnippetsHelper::getNamespace()->get('adapters/articles/no_shop_id', 'Shop by id %s not found');
            throw new AdapterException(\sprintf($message, $id));
        }

        return $shop;
    }

    /**
     * @return array<array<string, string>>
     */
    public function getSections(): array
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
            ['id' => 'translation', 'name' => 'translation'],
        ];
    }

    /**
     * @return array<string>
     */
    public function getArticleColumns(): array
    {
        return \array_merge($this->getArticleVariantColumns(), $this->getVariantColumns());
    }

    /**
     * @return array<string>
     */
    public function getArticleVariantColumns(): array
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
     * @throws \Zend_Db_Statement_Exception
     *
     * @return array<string>
     */
    public function getArticleAttributes(): array
    {
        $stmt = $this->db->query('SHOW COLUMNS FROM `s_articles_attributes`');
        $columns = $stmt->fetchAll();

        $attributes = $this->filterAttributeColumns($columns);

        $attributesSelect = [];
        if ($attributes) {
            $prefix = 'attribute';
            foreach ($attributes as $attribute) {
                $attr = $this->underscoreToCamelCaseService->underscoreToCamelCase($attribute);

                if (empty($attr)) {
                    continue;
                }

                $attributesSelect[] = \sprintf('%s.%s as attribute%s', $prefix, $attr, \ucwords($attr));
            }
        }

        $attributesSelect = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesDbAdapter_GetArticleAttributes',
            $attributesSelect,
            ['subject' => $this]
        );

        return $attributesSelect;
    }

    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    /**
     * @return string[]
     */
    public function getParentKeys(string $section): array
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

        throw new \RuntimeException(sprintf('No case found for section "%s"', $section));
    }

    /**
     * @return string[]
     */
    public function getVariantColumns(): array
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
            'variant.lastStock as lastStock',
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
            'variant.purchasePrice as purchasePrice',
        ];

        // Attributes
        $attributesSelect = $this->getArticleAttributes();

        if (!empty($attributesSelect)) {
            $columns = \array_merge($columns, $attributesSelect);
        }

        return $columns;
    }

    /**
     * @return array<string>
     */
    public function getPriceColumns(): array
    {
        $columns = [
            'prices.articleDetailsId as variantId',
            'prices.articleId as articleId',
            'prices.price as price',
            'prices.pseudoPrice as pseudoPrice',
            'prices.customerGroupKey as priceGroup',
            'prices.from',
            'prices.to',
        ];

        if (SwagVersionHelper::isShopware578()) {
            $columns[] = 'prices.regulationPrice as regulationPrice';
        }

        return $columns;
    }

    /**
     * @return array<string>
     */
    public function getImageColumns(): array
    {
        return [
            'images.id as id',
            'images.articleId as articleId',
            'images.articleDetailId as variantId',
            'images.path as path',
            "CONCAT('media/image/', images.path, '.', images.extension) as imageUrl",
            'images.main as main',
            'images.mediaId as mediaId',
            ' \'1\' as thumbnail',
        ];
    }

    /**
     * @return array<string>
     */
    public function getPropertyValueColumns(): array
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
     * @return array<string>
     */
    public function getSimilarColumns(): array
    {
        return [
            'similar.id as similarId',
            'similarDetail.number as ordernumber',
            'article.id as articleId',
        ];
    }

    /**
     * @return array<string>
     */
    public function getAccessoryColumns(): array
    {
        return [
            'accessory.id as accessoryId',
            'accessoryDetail.number as ordernumber',
            'article.id as articleId',
        ];
    }

    /**
     * @return array<string>
     */
    public function getConfiguratorColumns(): array
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
     * @return array<string>
     */
    public function getCategoryColumns(): array
    {
        return [
            'categories.id as categoryId',
            'categories.path as categoryPath',
            'article.id as articleId',
        ];
    }

    /**
     * @return array<string>
     */
    public function getTranslationColumns(): array
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
            'translation.shippingtime as shippingTime',
        ];

        $attributes = $this->getTranslatableAttributes();

        if ($attributes) {
            foreach ($attributes as $attribute) {
                $columns[] = $attribute['columnName'];
            }
        }

        return $columns;
    }

    /**
     * @throws \RuntimeException
     */
    public function saveMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \RuntimeException($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

    /**
     * @return array<string>
     */
    public function getLogMessages(): array
    {
        return $this->logMessages;
    }

    public function setLogMessages(string $logMessages): void
    {
        $this->logMessages[] = $logMessages;
    }

    public function getLogState(): ?string
    {
        return $this->logState;
    }

    public function setLogState(string $logState): void
    {
        $this->logState = $logState;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveUnprocessedData(string $profileName, string $type, string $articleNumber, array $data): void
    {
        $this->saveArticleData($articleNumber);

        $this->setUnprocessedData($profileName, $type, $data);
    }

    public function getUnprocessedData(): array
    {
        return $this->unprocessedData;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setUnprocessedData(string $profileName, string $type, array $data): void
    {
        $this->unprocessedData[$profileName][$type][] = $data;
    }

    /**
     * @return array<string, string>|null
     */
    public function getTempData(): ?array
    {
        return $this->tempData;
    }

    public function setTempData(string $tempData): void
    {
        $this->tempData[$tempData] = $tempData;
    }

    /**
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getArticleBuilder(array $columns, array $ids): QueryBuilder
    {
        $articleBuilder = $this->modelManager->createQueryBuilder();
        $articleBuilder->select($columns)
            ->from(Detail::class, 'variant')
            ->join('variant.article', 'article')
            ->leftJoin(Detail::class, 'mv', Join::WITH, 'mv.articleId=article.id AND mv.kind=1')
            ->leftJoin('variant.attribute', 'attribute')
            ->leftJoin('article.tax', 'articleTax')
            ->leftJoin('article.supplier', 'supplier')
            ->leftJoin('article.propertyGroup', 'filterGroup')
            ->leftJoin('article.esds', 'articleEsd')
            ->leftJoin('variant.unit', 'variantsUnit')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
            ->orderBy('variant.kind');

        return $articleBuilder;
    }

    /**
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getPriceBuilder(array $columns, array $ids): QueryBuilder
    {
        $priceBuilder = $this->modelManager->createQueryBuilder();
        $priceBuilder->select($columns)
            ->from(Detail::class, 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('variant.prices', 'prices')
            ->leftJoin('prices.customerGroup', 'customerGroup')
            ->leftJoin('article.tax', 'articleTax')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $priceBuilder;
    }

    /**
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getImageBuilder(array $columns, array $ids): QueryBuilder
    {
        $imageBuilder = $this->modelManager->createQueryBuilder();
        $imageBuilder->select($columns)
            ->from(Detail::class, 'variant')
            ->join('variant.article', 'article')
            ->leftJoin('article.images', 'images')
            ->where('variant.id IN (:ids)')
            ->andWhere('variant.kind = 1')
            ->andWhere('images.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $imageBuilder;
    }

    /**
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getPropertyValueBuilder(array $columns, array $ids): QueryBuilder
    {
        $propertyValueBuilder = $this->modelManager->createQueryBuilder();
        $propertyValueBuilder->select($columns)
            ->from(Detail::class, 'variant')
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
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getConfiguratorBuilder(array $columns, array $ids): QueryBuilder
    {
        $configBuilder = $this->modelManager->createQueryBuilder();
        $configBuilder->select($columns)
            ->from(Detail::class, 'variant')
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
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getSimilarBuilder(array $columns, array $ids): QueryBuilder
    {
        $similarBuilder = $this->modelManager->createQueryBuilder();
        $similarBuilder->select($columns)
            ->from(Detail::class, 'variant')
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
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getAccessoryBuilder(array $columns, array $ids): QueryBuilder
    {
        $accessoryBuilder = $this->modelManager->createQueryBuilder();
        $accessoryBuilder->select($columns)
            ->from(Detail::class, 'variant')
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
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function getCategoryBuilder(array $columns, array $ids): QueryBuilder
    {
        $categoryBuilder = $this->modelManager->createQueryBuilder();
        $categoryBuilder->select($columns)
            ->from(Article::class, 'article')
            ->leftJoin('article.categories', 'categories')
            ->where('article.id IN (:ids)')
            ->andWhere('categories.id IS NOT NULL')
            ->setParameter('ids', $ids);

        return $categoryBuilder;
    }

    /**
     * @param array<int> $detailIds
     *
     * @return array<int>
     */
    protected function getArticleIdsByDetailIds(array $detailIds): array
    {
        $productIds = $this->modelManager->createQueryBuilder()
            ->select('article.id')
            ->from(Detail::class, 'variant')
            ->join('variant.article', 'article')
            ->where('variant.id IN (:ids)')
            ->setParameter('ids', $detailIds)
            ->groupBy('article.id');

        return \array_map(
            function ($item) {
                return (int) $item['id'];
            },
            $productIds->getQuery()->getResult()
        );
    }

    /**
     * Collects and creates a helper mapper for category path
     *
     * @param array<string, mixed> $categories
     *
     * @return array<int, string>
     */
    protected function getAssignedCategoryNames(array $categories): array
    {
        $categoryIds = [];
        foreach ($categories as $category) {
            if (!empty($category['categoryId'])) {
                $categoryIds[] = (string) $category['categoryId'];
            }

            if (!empty($category['categoryPath'])) {
                $catPath = \explode('|', $category['categoryPath']);
                $categoryIds = \array_merge($categoryIds, $catPath);
            }
        }

        // only unique ids
        $categoryIds = \array_unique($categoryIds);

        // removes empty value
        $categoryIds = \array_filter($categoryIds);

        $categoriesNames = $this->modelManager->createQueryBuilder()
            ->select(['category.id, category.name'])
            ->from(Category::class, 'category')
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
     * @param array<string, mixed> $category contains category data
     * @param array<string>        $mapper   contains categories' names
     */
    protected function generatePath(array $category, array $mapper): string
    {
        $ids = [];
        if (!empty($category['categoryPath'])) {
            foreach (\explode('|', $category['categoryPath']) as $id) {
                $ids[] = $mapper[$id];
            }
        }
        \krsort($ids);

        if (!empty($category['categoryId'])) {
            $ids[] = $mapper[$category['categoryId']];
        }

        $ids = \array_filter($ids);

        return \implode('->', $ids);
    }

    /**
     * This data is for matching similars and accessories
     */
    protected function saveArticleData(string $articleNumber): void
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
            'processed' => 1,
        ];

        $this->setUnprocessedData('articles', 'article', $articleData);
    }

    /**
     * Collects recursively category ids
     *
     * @param array<int> $categoriesReturn
     */
    protected function collectCategoryIds(Category $categoryModel, array &$categoriesReturn): void
    {
        $categoryId = $categoryModel->getId();
        $categoriesReturn[] = $categoryId;
        $categories = $categoryModel->getChildren();

        if (!$categories) {
            return;
        }

        foreach ($categories as $category) {
            $this->collectCategoryIds($category, $categoriesReturn);
        }
    }

    /**
     * Returns all fields that can be translated.
     *
     * @return array<string, string>
     */
    private function getTranslationFields(): array
    {
        $translationFields = [
            'metaTitle' => 'metaTitle',
            'txtArtikel' => 'name',
            'txtkeywords' => 'keywords',
            'txtpackunit' => 'packUnit',
            'txtzusatztxt' => 'additionalText',
            'txtshortdescription' => 'description',
            'txtlangbeschreibung' => 'descriptionLong',
            'txtshippingtime' => 'shippingTime',
        ];

        $attributes = $this->getTranslatableAttributes();
        foreach ($attributes as $attribute) {
            $translationFields['__attribute_' . $attribute['columnName']] = $attribute['columnName'];
        }

        return $translationFields;
    }

    /**
     * Return list with default values for fields which are empty or don't exists
     *
     * @return array<string>
     */
    private function getDefaultValues(): array
    {
        return $this->defaultValues;
    }

    /**
     * @param array<string, mixed> $records
     *
     * @throws \Exception
     */
    private function performImport(array $records): void
    {
        $articleWriter = $this->articleWriter;
        $pricesWriter = $this->priceWriter;
        $categoryWriter = $this->categoryWriter;
        $configuratorWriter = $this->configuratorWriter;
        $translationWriter = $this->translationWriter;
        $propertyWriter = $this->propertyWriter;
        $relationWriter = $this->relationWriter;
        $imageWriter = $this->imageWriter;
        $imageWriter->setArticleDBAdapter($this);
        $relationWriter->setArticlesDbAdapter($this);

        $defaultValues = $this->getDefaultValues();
        $this->setDefaultValues([]);
        $this->unprocessedData = [];
        $this->tempData = [];

        foreach ($records['article'] as $index => $article) {
            try {
                $this->modelManager->getConnection()->beginTransaction();

                $articleWriterResult = $articleWriter->write($article, $defaultValues);

                $processedFlag = isset($article['processed']) && (int) $article['processed'] === 1;

                /*
                 * Only processed data will be imported
                 */
                if (!$processedFlag) {
                    $pricesWriter->write(
                        $articleWriterResult->getArticleId(),
                        $articleWriterResult->getDetailId(),
                        \array_filter(
                            $records['price'] ?? [],
                            function ($price) use ($index) {
                                return (int) $price['parentIndexElement'] === $index;
                            }
                        )
                    );

                    $categoryWriter->write(
                        $articleWriterResult->getArticleId(),
                        \array_filter(
                            $records['category'] ?? [],
                            function ($category) use ($index) {
                                return (int) $category['parentIndexElement'] === $index
                                    && ($category['categoryId'] || $category['categoryPath']);
                            }
                        )
                    );

                    $configuratorWriter->writeOrUpdateConfiguratorSet(
                        $articleWriterResult,
                        \array_filter(
                            $records['configurator'] ?? [],
                            function ($configurator) use ($index) {
                                return (int) $configurator['parentIndexElement'] === $index;
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
                        \array_filter(
                            $records['translation'] ?? [],
                            function ($translation) use ($index) {
                                return (int) $translation['parentIndexElement'] === $index;
                            }
                        )
                    );
                }

                /*
                 * Processed and unprocessed data will be imported
                 */
                if ($processedFlag) {
                    $article['mainNumber'] = $article['orderNumber'];
                }

                $relationWriter->write(
                    $articleWriterResult->getArticleId(),
                    $article['mainNumber'],
                    \array_filter(
                        $records['accessory'] ?? [],
                        function ($accessory) use ($index, $articleWriterResult) {
                            return (int) $accessory['parentIndexElement'] === $index
                                && $articleWriterResult->getMainDetailId() === $articleWriterResult->getDetailId();
                        }
                    ),
                    'accessory',
                    $processedFlag
                );

                $relationWriter->write(
                    $articleWriterResult->getArticleId(),
                    $article['mainNumber'],
                    \array_filter(
                        $records['similar'] ?? [],
                        function ($similar) use ($index, $articleWriterResult) {
                            return (int) $similar['parentIndexElement'] === $index
                                && $articleWriterResult->getMainDetailId() === $articleWriterResult->getDetailId();
                        }
                    ),
                    'similar',
                    $processedFlag
                );

                $imageWriter->write(
                    $articleWriterResult->getArticleId(),
                    $article['mainNumber'],
                    \array_filter(
                        $records['image'] ?? [],
                        function ($image) use ($index) {
                            return (int) $image['parentIndexElement'] === $index;
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
     * @return array<array<string, string>>
     */
    private function getTranslatableAttributes(): array
    {
        $repository = $this->modelManager->getRepository(Configuration::class);

        return $repository->createQueryBuilder('configuration')
            ->select('configuration.columnName')
            ->where('configuration.tableName = :tablename')
            ->andWhere('configuration.translatable = 1')
            ->setParameter('tablename', 's_articles_attributes')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array<string, mixed> $records
     *
     * @return array<string|int, array<string, string>>
     */
    private function filterPropertyValues(array $records, int $index, ArticleWriterResult $articleWriterResult): array
    {
        return \array_filter(
            $records['propertyValue'] ?? [],
            function ($property) use ($index, $articleWriterResult) {
                return (int) $property['parentIndexElement'] === $index
                    && $articleWriterResult->getMainDetailId() === $articleWriterResult->getDetailId();
            }
        );
    }

    /**
     * @param array<mixed> $columns
     *
     * @return array<string>
     */
    private function filterAttributeColumns(array $columns): array
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
