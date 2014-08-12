<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataColumnOptions
{

    private $columnOptions;

    public function __construct($columnOptions)
    {
        $this->columnOptions = $columnOptions;
    }

    public function getColumnOptions()
    {
        return $this->columnOptions;
    }

}