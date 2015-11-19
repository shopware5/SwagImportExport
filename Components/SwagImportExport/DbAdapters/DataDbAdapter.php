<?php

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
