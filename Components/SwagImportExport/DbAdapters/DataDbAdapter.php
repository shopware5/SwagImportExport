<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

interface DataDbAdapter
{

    public function read($ids, $columns);
}
