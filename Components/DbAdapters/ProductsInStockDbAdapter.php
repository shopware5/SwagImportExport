<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Repository;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\ProductInStockValidator;

class ProductsInStockDbAdapter implements DataDbAdapter, \Enlight_Hook
{
    protected ModelManager $modelManager;

    protected array $logMessages = [];

    protected ?string $logState = null;

    protected Repository $repository;

    protected ProductInStockValidator $validator;

    private \Enlight_Event_EventManager $eventManager;

    private \Shopware_Components_Config $config;

    public function __construct(
        ModelManager $modelManager,
        \Enlight_Event_EventManager $eventManager,
        \Shopware_Components_Config $config
    ) {
        $this->validator = new ProductInStockValidator();
        $this->modelManager = $modelManager;
        $this->repository = $this->modelManager->getRepository(Detail::class);
        $this->eventManager = $eventManager;
        $this->config = $config;
    }

    public function supports(string $adapter): bool
    {
        return $adapter === DataDbAdapter::PRODUCT_INSTOCK_ADAPTER;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultColumns(): array
    {
        return [
            'variant.number as orderNumber',
            'variant.inStock as inStock',
            'article.name as name',
            'variant.additionalText as additionalText',
            'articleSupplier.name as supplier',
            'prices.price as price',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function read(array $ids, array $columns): array
    {
        if (empty($ids)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles_no_ids', 'Can not read articles without ids.');
            throw new \Exception($message);
        }

        // prices
        array_push($columns, 'customerGroup.taxInput as taxInput', 'articleTax.tax as tax');

        $query = $this->getBuilder($columns, $ids)->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $result['default'] = $this->modelManager->createPaginator($query)->getIterator()->getArrayCopy();

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
     * {@inheritDoc}
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []): array
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
                throw new \Exception('Cannot match StockFilter - File:ProductsInStockAdapter Line:' . __LINE__);
        }

        if (isset($filter['stockFilter'])) {
            unset($filter['stockFilter']);
        }

        $builder->andWhere("p.customerGroupKey = 'EK'")
            ->andWhere('p.from = 1')
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
        $records = $this->modelManager->createPaginator($query)->getIterator()->getArrayCopy();
        $result = [];
        if ($records) {
            foreach ($records as $value) {
                $result[] = $value['id'];
            }
        }

        return $result;
    }

    /**
     * @throws \Enlight_Event_Exception
     * @throws \Exception
     */
    public function write(array $records): void
    {
        $productCount = 0;

        if (empty($records['default'])) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articlesInStock/no_records', 'No article stock records were found.');
            throw new \Exception($message);
        }

        $records = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_ArticlesInStockDbAdapter_Write',
            $records,
            ['subject' => $this]
        );

        foreach ($records['default'] as $record) {
            try {
                $record = $this->validator->filterEmptyString($record);
                $this->validator->checkRequiredFields($record);
                $this->validator->validate($record, ProductInStockValidator::$mapper);

                $productDetail = $this->repository->findOneBy(['number' => $record['orderNumber']]);
                if (!$productDetail instanceof Detail) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesImages/article_not_found', 'Product with number %s does not exist.');
                    throw new AdapterException(\sprintf($message, $record['orderNumber']));
                }

                $inStock = (int) $record['inStock'];

                $productDetail->setInStock($inStock);

                if (($productCount % 50) === 0) {
                    $this->modelManager->flush();
                }
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }

        $this->modelManager->flush();
    }

    public function getSections(): array
    {
        return [
            [
                'id' => 'default',
                'name' => 'default ',
            ],
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
     * @param array<array<string>|string> $columns
     * @param array<int>                  $ids
     */
    public function getBuilder(array $columns, array $ids): QueryBuilder
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
            ->setParameter('ids', $ids)
            ->setParameter('customergroup', 'EK');

        return $builder;
    }
}
