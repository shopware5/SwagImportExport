<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Shopware\Bundle\SearchBundle\ProductNumberSearchInterface;
use Shopware\Bundle\SearchBundle\StoreFrontCriteriaFactoryInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\ProductStream\Repository;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price as ArticlePrice;
use Shopware\Models\Category\Category;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Models\Shop\Shop;
use Shopware\Models\Tax\Tax;
use SwagImportExport\Components\DataManagers\ArticlePriceDataManager;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Utils\SwagVersionHelper;
use SwagImportExport\Components\Validators\ArticlePriceValidator;

class ArticlesPricesDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    protected ModelManager $manager;

    /**
     * @return array<array<int>>
     */
    protected array $categoryIdCollection;

    /**
     * @var array<string>
     */
    protected array $logMessages = [];

    protected ?string $logState = null;

    protected array $unprocessedData;

    protected ArticlePriceValidator $validator;

    protected ArticlePriceDataManager $dataManager;

    private ContextServiceInterface $contextService;

    private StoreFrontCriteriaFactoryInterface $storeFrontCriteriaFactory;

    private Repository $productStreamRepository;

    private ProductNumberSearchInterface $productNumberSearch;

    private \Enlight_Event_EventManager $eventManager;

    private \Shopware_Components_Config $config;

    public function __construct(
        ArticlePriceDataManager $dataManager,
        ModelManager $manager,
        ContextServiceInterface $contextService,
        StoreFrontCriteriaFactoryInterface $storeFrontCriteriaFactory,
        Repository $productStreamRepository,
        ProductNumberSearchInterface $productNumberSearch,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Config $config
    ) {
        $this->dataManager = $dataManager;
        $this->validator = new ArticlePriceValidator();
        $this->manager = $manager;
        $this->contextService = $contextService;
        $this->storeFrontCriteriaFactory = $storeFrontCriteriaFactory;
        $this->productStreamRepository = $productStreamRepository;
        $this->productNumberSearch = $productNumberSearch;
        $this->eventManager = $eventManager;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int>
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select('price.id')
            ->from(Article::class, 'article')
            ->leftJoin('article.details', 'detail')
            ->leftJoin('detail.prices', 'price')
            ->andWhere('price.price > 0')
            ->orderBy('price.id', 'ASC');

        if (!empty($filter)) {
            if ($filter['variants']) {
                $builder->andWhere('detail.kind <> 3');
            } else {
                $builder->andWhere('detail.kind = 1');
            }

            if (isset($filter['categories'])) {
                /** @var Category $category */
                $category = $this->manager->find(Category::class, $filter['categories'][0]);

                $this->collectCategoryIds($category);
                $categories = $this->getCategoryIdCollection();

                $categoriesBuilder = $this->manager->createQueryBuilder();
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

                $builder
                    ->andWhere('article.id IN (:ids)')
                    ->setParameter('ids', $articleIds);
            } else {
                if (isset($filter['productStreamId'])) {
                    $productStreamId = $filter['productStreamId'][0];

                    /** @var \Shopware\Models\Shop\Repository $shopRepo */
                    $shopRepo = $this->manager->getRepository(Shop::class);
                    $shop = $shopRepo->getActiveDefault();
                    $context = $this->contextService->createShopContext($shop->getId());
                    $criteria = $this->storeFrontCriteriaFactory->createBaseCriteria([$shop->getCategory()->getId()], $context);
                    $this->productStreamRepository->prepareCriteria($criteria, $productStreamId);
                    $products = $this->productNumberSearch->search($criteria, $context);
                    $productNumbers = \array_keys($products->getProducts());

                    $builder->andWhere('detail.number IN(:productNumbers)')
                        ->setParameter('productNumbers', $productNumbers);
                }
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
            foreach ($records as $value) {
                if (isset($value['id'])) {
                    $result[] = (int) $value['id'];
                }
            }
        }

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_ids', 'Can not read articles without ids');
            throw new \Exception($message);
        }

        if (empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_column_names', 'Can not read articles without column names.');
            throw new \Exception($message);
        }

        $columns = \array_merge($columns, ['customerGroup.taxInput as taxInput', 'articleTax.tax as tax']);

        $builder = $this->getBuilder($columns, $ids);

        $result['default'] = $builder->getQuery()->getResult();

        // add the tax if needed
        foreach ($result['default'] as &$record) {
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
                    $record['regulationPrice'] = \round($record['regulationPrice'], 2);
                }
            }

            if ($record['purchasePrice']) {
                $record['purchasePrice'] = \round($record['purchasePrice'], 2);
            }
        }

        return $result;
    }

    public function getDefaultColumns(): array
    {
        $columns = [
            'detail.number as orderNumber',
            'price.id',
            'price.articleId',
            'price.articleDetailsId',
            'price.from',
            'price.to',
            'price.price',
            'price.pseudoPrice',
            'price.percent',
            'price.customerGroupKey as priceGroup',
            'article.name as name',
            'detail.additionalText as additionalText',
            'detail.purchasePrice as purchasePrice',
            'supplier.name as supplierName',
        ];

        if (SwagVersionHelper::isShopware578()) {
            $columns[] = 'price.regulationPrice';
        }

        return $columns;
    }

    public function getUnprocessedData(): array
    {
        return $this->unprocessedData;
    }

    /**
     * Imports the records. <br/>
     * <b>Note:</b> The logic is copied from the old Import/Export Module
     *
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write(array $records): void
    {
        $this->unprocessedData = [];

        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articlesPrices/no_records',
                'No article price records were found.'
            );
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesPricesDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        $customerGroupRepository = $this->manager->getRepository(CustomerGroup::class);
        $detailRepository = $this->manager->getRepository(Detail::class);
        $flushCounter = 0;

        foreach ($records['default'] as $record) {
            try {
                ++$flushCounter;
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $record = $this->dataManager->setDefaultFields($record);
                $this->validator->validate($record, ArticlePriceValidator::$mapper);

                /** @var CustomerGroup $customerGroup */
                $customerGroup = $customerGroupRepository->findOneBy(['key' => $record['priceGroup']]);
                if (!$customerGroup) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesPrices/price_group_not_found', 'Price group %s was not found');
                    throw new AdapterException(\sprintf($message, $record['priceGroup']));
                }

                /** @var Detail $articleDetail */
                $articleDetail = $detailRepository->findOneBy(['number' => $record['orderNumber']]);
                if (!$articleDetail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/article_number_not_found', 'Article with order number %s does not exists');
                    throw new AdapterException(\sprintf($message, $record['orderNumber']));
                }

                if (isset($record['from'])) {
                    $record['from'] = (int) $record['from'];
                }

                if (empty($record['price']) && empty($record['percent'])) {
                    $message = SnippetsHelper::getNamespace()->get(
                        'adapters/articlesPrices/price_percent_val_missing',
                        'Price or percent value is missing'
                    );
                    throw new AdapterException($message);
                }

                if ($record['from'] <= 1 && empty($record['price'])) {
                    $message = SnippetsHelper::getNamespace()->get(
                        'adapters/articlesPrices/price_val_missing',
                        'Price value is missing'
                    );
                    throw new AdapterException($message);
                }

                if (isset($record['price'])) {
                    $record['price'] = (float) \str_replace(',', '.', (string) $record['price']);
                }

                if (isset($record['pseudoPrice'])) {
                    $record['pseudoPrice'] = (float) \str_replace(',', '.', (string) $record['pseudoPrice']);
                }

                if (isset($record['regulationPrice'])) {
                    $record['regulationPrice'] = (float) \str_replace(',', '.', (string) $record['regulationPrice']);
                }

                if (isset($record['purchasePrice'])) {
                    $record['purchasePrice'] = (float) \str_replace(',', '.', (string) $record['purchasePrice']);
                }

                if (isset($record['percent'])) {
                    $record['percent'] = (float) \str_replace(',', '.', (string) $record['percent']);
                }

                // removes price with same from value from database
                $this->updateArticleFromPrice($record, $articleDetail->getId());
                // checks if price belongs to graduation price
                if ((int) $record['from'] !== 1) {
                    // updates graduation to value with from value - 1
                    $this->updateArticleToPrice($record, $articleDetail->getId(), $articleDetail->getArticleId());
                }

                // remove tax
                if ($customerGroup->getTaxInput()) {
                    $tax = $articleDetail->getArticle()->getTax();
                    if ($tax instanceof Tax) {
                        $record['price'] = $record['price'] / (100 + (float) $tax->getTax()) * 100;
                        $record['pseudoPrice'] = $record['pseudoPrice'] / (100 + (float) $tax->getTax()) * 100;
                        if (SwagVersionHelper::isShopware578()) {
                            $record['regulationPrice'] = $record['regulationPrice'] / (100 + (float) $tax->getTax()) * 100;
                        }
                    }
                }

                $price = new ArticlePrice();
                $price->setArticle($articleDetail->getArticle());
                $price->setDetail($articleDetail);
                $price->setCustomerGroup($customerGroup);
                $price->setFrom($record['from']);
                $price->setTo($record['to']);
                $price->setPrice($record['price']);

                if (isset($record['pseudoPrice'])) {
                    $price->setPseudoPrice($record['pseudoPrice']);
                }

                if (SwagVersionHelper::isShopware578() && isset($record['regulationPrice'])) {
                    $price->setRegulationPrice($record['regulationPrice']);
                }

                if (isset($record['purchasePrice'])) {
                    $articleDetail->setPurchasePrice($record['purchasePrice']);
                }

                $price->setPercent($record['percent']);

                $this->manager->persist($price);

                // perform entitymanager flush every 20th record to improve performance
                if (($flushCounter % 20) === 0) {
                    $this->manager->flush();
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
        // perform final db flush at the end
        $this->manager->flush();
    }

    public function getSections(): array
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    public function getColumns(string $section): array
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return [];
    }

    public function saveMessage(string $message): void
    {
        $errorMode = $this->config->get('SwagImportExportErrorMode');

        if ($errorMode === false) {
            throw new \Exception($message);
        }

        $this->setLogMessages($message);
        $this->setLogState('true');
    }

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
     * @return array<array<int>>
     */
    public function getCategoryIdCollection(): array
    {
        return $this->categoryIdCollection;
    }

    /**
     * @param array<int> $categoryIdCollection
     */
    public function setCategoryIdCollection(array $categoryIdCollection): void
    {
        $this->categoryIdCollection[] = $categoryIdCollection;
    }

    /**
     * @param array<array<string>|string> $columns
     * @param array<int>                  $ids
     */
    public function getBuilder(array $columns, array $ids): QueryBuilder
    {
        $builder = $this->manager->createQueryBuilder();

        $builder->select($columns)
            ->from(Article::class, 'article')
            ->leftJoin('article.details', 'detail')
            ->leftJoin('article.tax', 'articleTax')
            ->leftJoin('article.supplier', 'supplier')
            ->leftJoin('detail.prices', 'price')
            ->leftJoin('price.customerGroup', 'customerGroup')
            ->where('price.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $builder;
    }

    protected function collectCategoryIds(Category $categoryModel): void
    {
        $categoryId = $categoryModel->getId();
        $this->setCategoryIdCollection([$categoryId]);
        $categories = $categoryModel->getChildren();

        if (!$categories) {
            return;
        }

        foreach ($categories as $category) {
            $this->collectCategoryIds($category);
        }
    }

    /**
     * @param array<string, mixed> $record
     */
    private function updateArticleFromPrice(array $record, int $articleDetailId): void
    {
        $dql = 'DELETE FROM Shopware\Models\Article\Price price
                WHERE price.customerGroup = :customerGroup
                AND price.articleDetailsId = :detailId
                AND price.from = :fromValue';

        $query = $this->manager->createQuery($dql);

        $query->setParameter('customerGroup', $record['priceGroup'])
            ->setParameter('detailId', $articleDetailId)
            ->setParameter('fromValue', $record['from'])
            ->execute();
    }

    /**
     * @param array<string, mixed> $record
     */
    private function updateArticleToPrice(array $record, int $articleDetailId, int $articleId): void
    {
        $dql = "UPDATE Shopware\Models\Article\Price price SET price.to = :toValue
                WHERE price.customerGroup = :customerGroup
                AND price.articleDetailsId = :detailId
                AND price.articleId = :articleId
                AND price.to LIKE 'beliebig'";

        $query = $this->manager->createQuery($dql);

        $query->setParameter('toValue', $record['from'] - 1)
            ->setParameter('customerGroup', $record['priceGroup'])
            ->setParameter('detailId', $articleDetailId)
            ->setParameter('articleId', $articleId)
            ->execute();
    }
}
