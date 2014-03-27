<?php

namespace Shopware\Components\SwagImportExport;

class DataIO
{

    /**
     * @var object Shopware\Components\DbAdapters
     */
    private $dbAdapter;

    /**
     * @var Shopware\Components\SwagImportExport\Utils\DataColumnOptions
     */
    private $columnOptions;

    /**
     * @var Shopware\Components\SwagImportExport\Utils\DataLimit
     */
    private $limit;

    /**
     * @var Shopware\Components\SwagImportExport\Utils\DataFilter
     */
    private $filter;

    public function __construct($dbAdapter, $colOpts, $limit, $filter)
    {
        $this->dbAdapter = $dbAdapter;
        $this->columnOptions = $colOpts;
        $this->limit = $limit;
        $this->filter = $filter;
    }
    
    /**
     * 
     * @param int $records
     */
    public function read($records)
    {
        //todo: 
        //Check Exprot session
        //predloadIds
        
        $this->getDbAdapter()->read($ids, $columns);
    }
    
    
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

}
