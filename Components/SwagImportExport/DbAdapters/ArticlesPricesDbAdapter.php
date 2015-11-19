<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Validators\ArticlePriceValidator;
use Shopware\Components\SwagImportExport\DataManagers\ArticlePriceDataManager;
use Shopware\Models\Article\Detail as ArticleDetail;
use Shopware\Models\Article\Price as ArticlePrice;
use Shopware\Models\Article\Repository as ArticleRepository;
use Shopware\Models\Customer\Group as CustomerGroup;
use Shopware\Models\Customer\Repository as CustomerRepository;

class ArticlesPricesDbAdapter implements DataDbAdapter
{
    /**
     * @var ModelManager
     */
    protected $manager;

    protected $detailRepository;
    protected $priceRepository;
    protected $groupRepository;

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

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @return array
     */
    public function readRecordIds($start, $limit, $filter)
    {
        $manager = $this->getManager();

        $builder = $manager->createQueryBuilder();

        $builder->select('price.id')
            ->from('Shopware\Models\Article\Article', 'article')
            ->leftJoin('article.details', 'detail')
            ->leftJoin('detail.prices', 'price')
            ->andWhere('price.price > 0')
            ->orderBy('price.id', 'ASC');

        if (!empty($filter)) {
            $builder->addFilter($filter);
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

        $columns = array_merge($columns, array('customerGroup.taxInput as taxInput', 'articleTax.tax as tax'));

        $builder = $this->getBuilder($columns, $ids);

        $result['default'] = $builder->getQuery()->getResult();

        // add the tax if needed
        foreach ($result['default'] as &$record) {
            if ($record['taxInput']) {
                $record['price'] = str_replace('.', ',', round($record['price'] * (100 + $record['tax']) / 100, 2));
                $record['pseudoPrice'] = str_replace('.', ',', round($record['pseudoPrice'] * (100 + $record['tax']) / 100, 2));
            } else {
                $record['price'] = str_replace('.', ',', round($record['price'], 2));
                $record['pseudoPrice'] = str_replace('.', ',', round($record['pseudoPrice'], 2));
            }

            if ($record['basePrice']) {
                $record['basePrice'] = str_replace('.', ',', round($record['basePrice'], 2));
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDefaultColumns()
    {
        return array(
            'detail.number as orderNumber',
            'price.id',
            'price.articleId',
            'price.articleDetailsId',
            'price.from',
            'price.to',
            'price.price',
            'price.pseudoPrice',
            'price.basePrice',
            'price.percent',
            'price.customerGroupKey as priceGroup',
            'article.name as name',
            'detail.additionalText as additionalText',
            'supplier.name as supplierName',
//            'articleTax.id as taxId',
//            'articleTax.tax as tax',
        );
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
            array('subject' => $this)
        );

        $manager = $this->getManager();
        $validator = $this->getValidator();
        $dataManager = $this->getDataManager();

        foreach ($records['default'] as $record) {
            try {
                $record = $validator->prepareInitialData($record);
                $validator->checkRequiredFields($record);
                $record = $dataManager->setDefaultFields($record);
                $validator->validate($record, ArticlePriceValidator::$mapper);

                /** @var CustomerGroup $customerGroup */
                $customerGroup = $this->getGroupRepository()->findOneBy(array("key" => $record['priceGroup']));
                if (!$customerGroup) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('adapters/articlesPrices/price_group_not_found', 'Price group %s was not found');
                    throw new AdapterException(sprintf($message, $record['priceGroup']));
                }

                /** @var ArticleDetail $articleDetail */
                $articleDetail = $this->getDetailRepository()->findOneBy(array("number" => $record['orderNumber']));
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
                
                if (isset($record['basePrice'])) {
                    $record['basePrice'] = floatval(str_replace(",", ".", $record['basePrice']));
                }

                if (isset($record['percent'])) {
                    $record['percent'] = floatval(str_replace(",", ".", $record['percent']));
                }

                $dql = 'DELETE FROM Shopware\Models\Article\Price price
                        WHERE price.customerGroup = :customerGroup
                            AND price.articleDetailsId = :detailId
                            AND price.from = :from';

                $query = $manager->createQuery($dql);

                $query->setParameters(
                    array(
                        'customerGroup' => $record['priceGroup'],
                        'detailId' => $articleDetail->getId(),
                        'from' => $record['from'],
                    )
                );
                $query->execute();

                if ($record['from'] != 1) {
                    $dql = 'UPDATE Shopware\Models\Article\Price price SET price.to = :TO
                            WHERE price.customerGroup = :customerGroup
                            AND price.articleDetailsId = :detailId
                            AND price.articleId = :articleId AND price.to
                            LIKE \'beliebig\'';
                    $query = $manager->createQuery($dql);

                    $query->setParameters(
                        array(
                            'to' => $record['from'] - 1,
                            'customerGroup' => $record['priceGroup'],
                            'detailId' => $articleDetail->getId(),
                            'articleId' => $articleDetail->getArticle()->getId(),
                        )
                    );
                    $query->execute();
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
                $price->setTo('beliebig');
                $price->setPrice($record['price']);
                if (isset($record['pseudoPrice'])) {
                    $price->setPseudoPrice($record['pseudoPrice']);
                }
                $price->setBasePrice($record['basePrice']);
                $price->setPercent($record['percent']);

                $this->getManager()->persist($price);

                $this->getManager()->flush();
                $this->getManager()->clear();
            } catch (AdapterException $e) {
                $message = $e->getMessage();
                $this->saveMessage($message);
            }
        }
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return array(
            array('id' => 'default', 'name' => 'default ')
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
     * Returns article detail repository
     *
     * @return ArticleRepository
     */
    public function getDetailRepository()
    {
        if ($this->detailRepository === null) {
            $this->detailRepository = $this->getManager()->getRepository('Shopware\Models\Article\Detail');
        }

        return $this->detailRepository;
    }

    /**
     * Returns article detail repository
     *
     * @return CustomerRepository
     */
    public function getGroupRepository()
    {
        if ($this->groupRepository === null) {
            $this->groupRepository = $this->getManager()->getRepository('Shopware\Models\Customer\Group');
        }

        return $this->groupRepository;
    }

    /**
     * @return ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
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
        $builder = $this->getManager()->createQueryBuilder();
        $builder->select($columns)
            ->from('Shopware\Models\Article\Article', 'article')
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
     * @return ArticlePriceValidator
     */
    public function getValidator()
    {
        if ($this->validator === null) {
            $this->validator = new ArticlePriceValidator();
        }

        return $this->validator;
    }

    /**
     * @return ArticlePriceDataManager
     */
    public function getDataManager()
    {
        if ($this->dataManager === null) {
            $this->dataManager = new ArticlePriceDataManager();
        }

        return $this->dataManager;
    }
}
