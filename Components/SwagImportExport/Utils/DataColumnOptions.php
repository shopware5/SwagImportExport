<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataColumnOptions
{
    private $columnOptions;

    /**
     * @param $columnOptions
     */
    public function __construct($columnOptions)
    {
        $this->columnOptions = $columnOptions;
    }

    /**
     * @return mixed
     */
    public function getColumnOptions()
    {
        return $this->columnOptions;
    }
}
