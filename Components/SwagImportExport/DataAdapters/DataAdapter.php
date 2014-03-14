<?php

namespace Shopware\Components\SwagImportExport\DataAdapters;

use Shopware\Components\SwagImportExport\DataAdapters\DataAdapterLimit;

abstract class DataAdapter extends \Enlight_Class implements \Enlight_Hook
{
    protected $manager;
    
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
    
    /**
     * Internal helper function to get access to the entity manager.
     * @return \Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }
        return $this->manager;
    }
    
    
}
