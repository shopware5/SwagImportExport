<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

interface DataDbAdapter
{
    public const ARTICLE_ADAPTER = 'articles';
    public const ARTICLE_IMAGE_ADAPTER = 'articlesImages';
    public const ARTICLE_INSTOCK_ADAPTER = 'articlesInStock';
    public const ARTICLE_TRANSLATION_ADAPTER = 'articlesTranslations';
    public const ARTICLE_PRICE_ADAPTER = 'articlesPrices';
    public const CATEGORIES_ADAPTER = 'categories';
    public const CATEGORIES_TRANSLATION_ADAPTER = 'categoriesTranslations';
    public const ORDER_ADAPTER = 'orders';
    public const MAIN_ORDER_ADAPTER = 'mainOrders';
    public const CUSTOMER_ADAPTER = 'customers';
    public const CUSTOMER_COMPLETE_ADAPTER = 'customersComplete';
    public const NEWSLETTER_RECIPIENTS_ADAPTER = 'newsletter';
    public const TRANSLATION_ADAPTER = 'translations';
    public const ADDRESS_ADAPTER = 'addresses';

    /**
     * Reads all records with the given ids and selects the passed columns.
     *
     * @param array<int>                                 $ids
     * @param array<string>|array<string, array<string>> $columns
     *
     * @return array<string, mixed>
     */
    public function read(array $ids, array $columns);

    /**
     * Returns all ids for the given export with the given parameters.
     *
     * @param array<string, mixed> $filter
     *
     * @return array<int>
     */
    public function readRecordIds(?int $start, ?int $limit, array $filter = []);

    /**
     * Returns the default column.
     *
     * @see DataDbAdapter::getColumns()
     *
     * @return array<string>
     */
    public function getDefaultColumns();

    /**
     * Returns all iteration nodes, i.e. for articles it configuratiors, similar, ...
     *
     * @return array<array<string>>
     */
    public function getSections();

    /**
     * Returns all column names.
     *
     * @example:
     * [
     *  'address.id as id',
     *  'address.firstname as firstname'
     * ]
     *
     * @return bool|mixed
     */
    public function getColumns(string $section);

    /**
     * Creates, updates and validates the imported records.
     *
     * @param array<string, mixed> $records
     *
     * @return void
     */
    public function write(array $records);

    /**
     * Returns unprocessed data. This will be used every time if an import wants to create data which relies on created data.
     * For instance article images, similar or accessory articles.
     *
     * @return array<mixed>
     */
    public function getUnprocessedData();

    /**
     * Returns all log messages as an array.
     *
     * @return array<string>
     */
    public function getLogMessages();

    /**
     * Returns true if log messages are available.
     *
     * @return ?string
     */
    public function getLogState();
}
