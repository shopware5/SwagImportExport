<?php

namespace Shopware\Components\SwagImportExport\DbAdapters;

class DataDbAdapter
{
    private $dbAdapter;
    private $columnOptions;
    private $limit;
    private $filter;
    
    public function __construct($dbAdapter, $colOpts, $limit, $filter)
    {
        $this->dbAdapter = $dbAdapter;
        $this->columnOptions = $colOpts;
        $this->limit = $limit;
        $this->filter = $filter;
    }

    public function read()
    {
        
    }
    
    public function write(array $data)
    {
        
    }
}
