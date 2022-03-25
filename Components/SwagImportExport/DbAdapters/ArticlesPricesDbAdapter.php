<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Bundle\SearchBundle\ProductNumberSearchResult;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\DataManagers\ArticlePriceDataManager;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Utils\SwagVersionHelper;
use Shopware\Components\SwagImportExport\Validators\ArticlePriceValidator;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price as ArticlePrice;
use Shopware\Models\Category\Category;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Models\Shop\Shop;

class ArticlesPricesDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $categoryIdCollection;

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
    protected $unprocessedData;

    /**
     * @var ArticlePriceValidator
     */
    protected $validator;

    /**
     * @var ArticlePriceDataManager
     */
    protected $dataManager;

    public function __construct()
    {
        $this->dataManager = new ArticlePriceDataManager();
        $this->validator = new ArticlePriceValidator();
        $this->manager = Shopware()->Models();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<int>
     */
    public function readRecordIds($start, $limit, $filter)
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
                    $contextService = Shopware()->Container()->get('shopware_storefront.context_service');
                    $context = $contextService->createShopContext($shop->getId());
                    $criteriaService = Shopware()->Container()->get('shopware_search.store_front_criteria_factory');
                    $criteria = $criteriaService->createBaseCriteria([$shop->getCategory()->getId()], $context);
                    $streamRepo = Shopware()->Container()->get('shopware_product_stream.repository');
                    $streamRepo->prepareCriteria($criteria, $productStreamId);

                    $productNumberSearch = Shopware()->Container()->get('shopware_search.product_number_search');
                    /** @var ProductNumberSearchResult $products */
                    $products = $productNumberSearch->search($criteria, $context);
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
    public function read($ids, $columns)
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
                if (SwagVersionHelper::hasMinimumVersion('5.7.8')) {
                    $record['regulationPrice'] = \round($record['regulationPrice'] * (100 + $record['tax']) / 100, 2);
                }
            } else {
                $record['price'] = \round($record['price'], 2);
                $record['pseudoPrice'] = \round($record['pseudoPrice'], 2);
                if (SwagVersionHelper::hasMinimumVersion('5.7.8')) {
                    $record['regulationPrice'] = \round($record['regulationPrice'], 2);
                }
            }

            if ($record['purchasePrice']) {
                $record['purchasePrice'] = \round($record['purchasePrice'], 2);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
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

        if (SwagVersionHelper::hasMinimumVersion('5.7.8')) {
            $columns[] = 'price.regulationPrice';
        }

        return $columns;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * Imports the records. <br/>
     * <b>Note:</b> The logic is copied from the old Import/Export Module
     *
     * @param array $records
     *
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write($records)
    {
        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articlesPrices/no_records',
                'No article price records were found.'
            );
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
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
                    $record['price'] = (float) \str_replace(',', '.', $record['price']);
                }

                if (isset($record['pseudoPrice'])) {
                    $record['pseudoPrice'] = (float) \str_replace(',', '.', $record['pseudoPrice']);
                }

                if (isset($record['regulation_price'])) {
                    $record['regulation_price'] = (float) \str_replace(',', '.', $record['regulation_price']);
                }

                if (isset($record['purchasePrice'])) {
                    $record['purchasePrice'] = (float) \str_replace(',', '.', $record['purchasePrice']);
                }

                if (isset($record['percent'])) {
                    $record['percent'] = (float) \str_replace(',', '.', $record['percent']);
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
                    $record['price'] = $record['price'] / (100 + (float) $tax->getTax()) * 100;
                    $record['pseudoPrice'] = $record['pseudoPrice'] / (100 + (float) $tax->getTax()) * 100;
                    if (SwagVersionHelper::hasMinimumVersion('5.7.8')) {
                        $record['regulation_price'] = $record['regulation_price'] / (100 + (float) $tax->getTax()) * 100;
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

                if (SwagVersionHelper::hasMinimumVersion('5.7.8') && isset($record['regulation_price'])) {
                    $price->setRegulationPrice($record['regulation_price']);
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

    /**
     * @return array
     */
    public function getSections()
    {
        return [
            ['id' => 'default', 'name' => 'default '],
        ];
    }

    /**
     * @param string $section
     *
     * @return bool|mixed
     */
    public function getColumns($section)
    {
        $method = 'get' . \ucfirst($section) . 'Columns';

        if (\method_exists($this, $method)) {
            return $this->{$method}();
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function saveMessage($message)
    {
        $errorMode = Shopware()->Config()->get('SwagImportExportErrorMode');

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

    public function setCategoryIdCollection($categoryIdCollection)
    {
        $this->categoryIdCollection[] = $categoryIdCollection;
    }

    /**
     * @return QueryBuilder
     */
    public function getBuilder($columns, $ids)
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
    }

    private function updateArticleFromPrice($record, $articleDetailId)
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

    private function updateArticleToPrice($record, $articleDetailId, $articleId)
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
