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

    /**
     * Array of records ids
     * 
     * @var array
     */
    private $recordIds;
    

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
        //todo: get from where to start 
        $start = 0;
        $ids = $this->loadIds($start, $records);

        $columns = $this->getColumns();

        $dbAdapter = $this->getDbAdapter();
        $rawData = $dbAdapter->read($ids, $columns);
        
        return $rawData;
    }

    public function preloadRecordIds()
    {
        //todo: check from ExportSession        
        
        $dbAdapter = $this->getDbAdapter();
        $limitAdapater = $this->getLimitAdapter();
        $filterAdapter = $this->getFilterAdapter();

        $ids = $dbAdapter->readRecordIds(
                $limitAdapater->getOffset(), $limitAdapater->getLimit(), $filterAdapter->getFilter()
        );

        $this->setRecordIds($ids);
    }

    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    public function getColumnOptionsAdapter()
    {
        return $this->columnOptions;
    }

    public function getLimitAdapter()
    {
        return $this->limit;
    }

    public function getFilterAdapter()
    {
        return $this->filter;
    }

    public function getRecordIds()
    {
        return $this->recordIds;
    }

    public function setRecordIds($recordIds)
    {
        $this->recordIds = $recordIds;
    }

    /**
     * Returns db columns
     * 
     * @return string
     */
    public function getColumns()
    {
        $colOptions = $this->getColumnOptionsAdapter()->getColumnOptions();
        
        if ($colOptions === null || empty($colOptions)){
            $colOptions = $this->getDbAdapter()->getDefaultColumns();
        }
        
        return $colOptions;
    }

    /**
     * Returns number of ids
     * 
     * @param int $start
     * @param int $records
     * @return string
     * @throws \Exception
     */
    private function loadIds($start, $records)
    {
        $storedIds = $this->getRecordIds();

        if ($storedIds === null || empty($storedIds)) {
            throw new \Exception('No loaded records ids');
        }

        $filterIds = array();
        $counter = 0;

        foreach ($storedIds as $index => $param) {
            if ($index >= $start && $counter < $records) {
                $filterIds[] = $param['id'];
                $counter ++;
            }
        }

        return implode(',', $filterIds);
    }

}
