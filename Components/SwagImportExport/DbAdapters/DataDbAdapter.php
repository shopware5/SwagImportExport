<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters;

interface DataDbAdapter
{
    const ARTICLE_ADAPTER = 'articles';
    const ARTICLE_IMAGE_ADAPTER = 'articlesImages';
    const ARTICLE_INSTOCK_ADAPTER = 'articlesInStock';
    const ARTICLE_TRANSLATION_ADAPTER = 'articlesTranslations';
    const ARTICLE_PRICE_ADAPTER = 'articlesPrices';
    const CATEGORIES_ADAPTER = 'categories';
    const ORDER_ADAPTER = 'orders';
    const MAIN_ORDER_ADAPTER = 'mainOrders';
    const CUSTOMER_ADAPTER = 'customers';
    const NEWSLETTER_RECIPIENTS_ADAPTER = 'newsletter';
    const TRANSLATION_ADAPTER = 'translations';

    public function read($ids, $columns);

    public function readRecordIds($start, $limit, $filter);

    public function getDefaultColumns();

    public function getSections();

    public function getColumns($columns);

    public function write($records);

    public function getUnprocessedData();

    public function getLogMessages();

    public function getLogState();
}
