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
