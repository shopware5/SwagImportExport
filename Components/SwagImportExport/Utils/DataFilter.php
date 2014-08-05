<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataFilter
{

    private $filter;

    public function __construct($filter)
    {
        $this->filter = $filter;
    }

    public function getFilter()
    {
        return $this->filter;
    }

}
