<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\ArticlePriceValidator;
use Shopware\Components\SwagImportExport\DataManagers\ArticlePriceDataManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Price as ArticlePrice;
use Shopware\Models\Customer\Group as CustomerGroup;

class ArticlesPricesDbAdapter implements DataDbAdapter
{
    /** @var ModelManager */
    protected $manager;

    /** @var array */
    protected $logMessages;

    /** @var string */
    protected $logState;

    /** @var array */
    protected $unprocessedData;

    /** @var ArticlePriceValidator */
    protected $validator;

    /** @var ArticlePriceDataManager */
    protected $dataManager;

    public function __construct()
    {
        $this->dataManager = new ArticlePriceDataManager();
        $this->validator = new ArticlePriceValidator();
        $this->manager = Shopware()->Models();
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @return array
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
            $builder->addFilter($filter);
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
                $result[] = $value['id'];
            }
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
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_ids', 'Can not read articles without ids');
            throw new \Exception($message);
        }

        if (!$columns && empty($columns)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_column_names', 'Can not read articles without column names.');
            throw new \Exception($message);
        }

        $columns = array_merge($columns, ['customerGroup.taxInput as taxInput', 'articleTax.tax as tax']);

        $builder = $this->getBuilder($columns, $ids);

        $result['default'] = $builder->getQuery()->getResult();

        // add the tax if needed
        foreach ($result['default'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = round($record['price'] * (100 + $record['tax']) / 100, 2);
                $record['pseudoPrice'] = round($record['pseudoPrice'] * (100 + $record['tax']) / 100, 2);
            } else {
                $record['price'] = round($record['price'], 2);
                $record['pseudoPrice'] = round($record['pseudoPrice'], 2);
            }

            if ($record['purchasePrice']) {
                $record['purchasePrice'] = round($record['purchasePrice'], 2);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        return [
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
            'supplier.name as supplierName'
        ];
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
                $flushCounter++;
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $record = $this->dataManager->setDefaultFields($record);
                $this->validator->validate($record, ArticlePriceValidator::$mapper);

                /** @var CustomerGroup $customerGroup */
                $customerGroup = $customerGroupRepository->findOneBy(["key" => $record['priceGroup']]);
                if (!$customerGroup) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesPrices/price_group_not_found', 'Price group %s was not found');
                    throw new AdapterException(sprintf($message, $record['priceGroup']));
                }

                /** @var Detail $articleDetail */
                $articleDetail = $detailRepository->findOneBy(["number" => $record['orderNumber']]);
                if (!$articleDetail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/article_number_not_found', 'Article with order number %s does not exists');
                    throw new AdapterException(sprintf($message, $record['orderNumber']));
                }

                if (isset($record['from'])) {
                    $record['from'] = intval($record['from']);
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
                    $record['price'] = floatval(str_replace(",", ".", $record['price']));
                }

                if (isset($record['pseudoPrice'])) {
                    $record['pseudoPrice'] = floatval(str_replace(",", ".", $record['pseudoPrice']));
                }

                if (isset($record['purchasePrice'])) {
                    $record['purchasePrice'] = floatval(str_replace(",", ".", $record['purchasePrice']));
                }

                if (isset($record['percent'])) {
                    $record['percent'] = floatval(str_replace(",", ".", $record['percent']));
                }
                // removes price with same from value from database
                $this->updateArticleFromPrice($record, $articleDetail->getId());
                // checks if price belongs to graduation price
                if ($record['from'] != 1) {
                    // updates graduation to value with from value - 1
                    $this->updateArticleToPrice($record, $articleDetail->getId(), $articleDetail->getArticleId());
                }

                // remove tax
                if ($customerGroup->getTaxInput()) {
                    $tax = $articleDetail->getArticle()->getTax();
                    $record['price'] = $record['price'] / (100 + $tax->getTax()) * 100;
                    $record['pseudoPrice'] = $record['pseudoPrice'] / (100 + $tax->getTax()) * 100;
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
            ['id' => 'default', 'name' => 'default ']
        ];
    }

    /**
     * @param string $section
     * @return bool|mixed
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
     * @param $message
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
     * @param $columns
     * @param $ids
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

    private function updateArticleFromPrice($record, $articleDetailId)
    {
        $dql = 'DELETE FROM Shopware\Models\Article\Price price
                WHERE price.customerGroup = :customerGroup
                AND price.articleDetailsId = :detailId
                AND price.from = :fromValue';

        $query = $this->manager->createQuery($dql);

        $query->setParameters(
            [
                'customerGroup' => $record['priceGroup'],
                'detailId' => $articleDetailId,
                'fromValue' => $record['from'],
            ]
        );
        $query->execute();
    }

    private function updateArticleToPrice($record, $articleDetailId, $articleId)
    {
        $dql = "UPDATE Shopware\Models\Article\Price price SET price.to = :toValue
                WHERE price.customerGroup = :customerGroup
                AND price.articleDetailsId = :detailId
                AND price.articleId = :articleId 
                AND price.to LIKE 'beliebig'";

        $query = $this->manager->createQuery($dql);

        $query->setParameters(
            [
                'toValue' => $record['from'] - 1,
                'customerGroup' => $record['priceGroup'],
                'detailId' => $articleDetailId,
                'articleId' => $articleId,
            ]
        );
        $query->execute();
    }
}
