<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataFilter
{
    private $filter;

    /**
     * @param $filter
     */
    public function __construct($filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
