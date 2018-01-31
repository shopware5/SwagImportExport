<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Shopware\Components\SwagImportExport\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Models\Article\Detail;
use Shopware\Components\SwagImportExport\Validators\ArticleInStockValidator;

class ArticlesInStockDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    protected $modelManager;

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
     * @var \Shopware\Models\Article\Repository
     */
    protected $repository;

    /**
     * @var SnippetsHelper
     */
    private $snippetHelper;

    /**
     * @var ArticleInStockValidator
     */
    protected $validator;

    public function __construct()
    {
        $this->validator = new ArticleInStockValidator();
        $this->snippetHelper = new SnippetsHelper();
        $this->modelManager = Shopware()->Container()->get('models');
        $this->repository = $this->modelManager->getRepository(Detail::class);
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        return array(
            'variant.number as orderNumber',
            'variant.inStock as inStock',
            'article.name as name',
            'variant.additionalText as additionalText',
            'articleSupplier.name as supplier',
            'prices.price as price',
        );
    }

    /**
     * @param array $ids
     * @param array $columns
     * @return array
     * @throws \Exception
     */
    public function read($ids, $columns)
    {
        if (empty($ids)) {
            $message = $this->snippetHelper->getNamespace()
                ->get('adapters/articles_no_ids', 'Can not read articles without ids.');
            throw new \Exception($message);
        }

        //prices
        $columns = array_merge(
            $columns,
            ['customerGroup.taxInput as taxInput', 'articleTax.tax as tax']
        );

        $builder = $this->getBuilder($columns, $ids);

        $query = $builder->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->modelManager->createPaginator($query);

        $result['default'] = $paginator->getIterator()->getArrayCopy();

        foreach ($result['default'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = $record['price'] * (100 + $record['tax']) / 100;
            }

            if (!$record['inStock']) {
                $record['inStock'] = '0';
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getUnprocessedData()
    {
        return $this->unprocessedData;
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @return array
     * @throws \Exception
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $stockFilter = $filter['stockFilter'];
        if ($stockFilter === null) {
            $stockFilter = 'all';
        }

        $builder = $this->modelManager->createQueryBuilder();

        $builder->select('d.id')
            ->from(Detail::class, 'd')
            ->leftJoin('d.prices', 'p');

        switch ($stockFilter) {
            case 'all':
                $builder->where($builder->expr()->isNotNull('d.id'));
                break;
            case 'inStock':
                $builder->where('d.inStock > 0');
                break;
            case 'notInStock':
                $builder->where('d.inStock <= 0');
                break;
            case 'inStockOnSale':
                $builder->leftJoin('d.article', 'a')
                    ->where('d.inStock > 0')
                    ->andWhere('d.lastStock = 1');
                break;
            case 'notInStockOnSale':
                $builder->leftJoin('d.article', 'a')
                    ->where('d.inStock <= 0')
                    ->andWhere('d.lastStock = 1');
                break;
            case 'notInStockMinStock':
                $builder->where('d.stockMin >= d.inStock')
                    ->andWhere('d.stockMin > 0');
                break;
            case 'custom':
                switch ($filter['direction']) {
                    case 'greaterThan':
                        $builder->where('d.inStock >= :filterValue');
                        break;
                    case 'lessThan':
                        $builder->where('d.inStock <= :filterValue');
                        break;
                }
                $builder->setParameter('filterValue', (int) $filter['value']);

                // unset filterValues for prevent query errors
                if (isset($filter['direction'])) {
                    unset($filter['direction']);
                }
                if (isset($filter['value'])) {
                    unset($filter['value']);
                }

                break;
            default:
                throw new \Exception('Cannot match StockFilter - File:ArticlesInStockAdapter Line:' . __LINE__);
        }

        if (isset($filter['stockFilter'])) {
            unset($filter['stockFilter']);
        }

        $builder->andWhere("p.customerGroupKey = 'EK'")
            ->andWhere("p.from = 1")
            ->orderBy('d.id', 'ASC');

        if (!empty($filter)) {
            $builder->addFilter($filter);
        }

        if ($start) {
            $builder->setFirstResult($start);
        }

        if ($limit) {
            $builder->setMaxResults($limit);
        }

        $query = $builder->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);
        $paginator = $this->modelManager->createPaginator($query);

        $records = $paginator->getIterator()->getArrayCopy();
        $result = [];
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    /**
     * @param $records
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write($records)
    {
        $articleCount = 0;

        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesInStock/no_records', 'No article stock records were found.');
            throw new \Exception($message);
        }

        $records = Shopware()->Events()->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesInStockDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        foreach ($records['default'] as $record) {
            try {
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, ArticleInStockValidator::$mapper);

                $articleDetail = $this->repository->findOneBy(["number" => $record['orderNumber']]);
                if (!$articleDetail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/article_not_found', 'Article with number %s does not exists.');
                    throw new AdapterException(sprintf($message, $record['orderNumber']));
                }

                $inStock = (int) $record['inStock'];

                $articleDetail->setInStock($inStock);

                if (($articleCount % 50) === 0) {
                    $this->modelManager->flush();
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
        
        $this->modelManager->flush();
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            [
                'id' => 'default',
                'name' => 'default '
            ]
        );
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
        $builder = $this->modelManager->createQueryBuilder();
        $builder->select($columns)
            ->from(Detail::class, 'variant')
            ->leftJoin('variant.article', 'article')
            ->leftJoin('article.supplier', 'articleSupplier')
            ->leftJoin('variant.prices', 'prices')
            ->leftJoin('prices.customerGroup', 'customerGroup')
            ->leftJoin('article.tax', 'articleTax')
            ->where('variant.id IN (:ids)')
            ->andWhere('prices.customerGroup = :customergroup')
            ->setParameters([
                'ids' => $ids,
                'customergroup' => 'EK'
            ]);

        return $builder;
    }
}
