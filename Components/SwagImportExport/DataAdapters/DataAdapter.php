<?php

namespace Shopware\Components\SwagImportExport\DataAdapters;

use Shopware\Components\SwagImportExport\DataAdapters\DataAdapterLimit;

abstract class DataAdapter extends \Enlight_Class implements \Enlight_Hook
{
    protected $dataAdapterLimit;
    
    public function __construct()
    {
        $this->dataAdapterLimit = new DataAdapterLimit();
        
        parent::__construct();
    }
    
    public function getDataAdapterLimit()
    {
        return $this->dataAdapterLimit;
    }

    public function setDataAdapterLimit($dataAdapterLimi)
    {
        $this->dataAdapterLimit = $dataAdapterLimi;
    }
    
}
