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

    /**
     * Type of the dataIO - export/import
     * 
     * @var string
     */
    private $type;

    /**
     * Format of the doc - csv, xml
     * 
     * @var string
     */
    private $format;

    /**
     * @var int 
     */
    private $maxRecordCount;

    /**
     * @var Shopware\CustomModels\ImportExport\Session 
     */
    private $dataSession;

    public function __construct($dbAdapter, $colOpts, $limit, $filter, $dataSession, $type, $format, $maxRecordCount)
    {
        $this->dbAdapter = $dbAdapter;
        $this->columnOptions = $colOpts;
        $this->limit = $limit;
        $this->filter = $filter;
        $this->dataSession = $dataSession;
        $this->type = $type;
        $this->format = $format;
        $this->maxRecordCount = $maxRecordCount;
    }

    /**
     * 
     * @param int $records
     */
    public function read($records)
    {
        $start = $this->dataSession->getPosition();

        $ids = $this->loadIds($start, $records);

        $columns = $this->getColumns();

        $dbAdapter = $this->getDbAdapter();
        $rawData = $dbAdapter->read($ids, $columns);

        return $rawData;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DataIO
     */
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

        return $this;
    }

    /**
     * Returns the state of the session. 
     * active: 
     *     Session is running and we can read/write records.
     * stopped: 
     *     Session is stopped because we have reached the max number of records per operation.
     * new: 
     *     Session is brand new and still has no records ids. 
     * finished: 
     *     Session is finished but the output file is still not finished (in case of export) 
     *     or the final db save is yet not performed (in case of import). 
     * closed: 
     *     Session is closed, file is fully exported/imported
     */
    public function getSessionState()
    {
        return $this->dataSession->getState();
    }

    /**
     * Check if the session contains ids.
     * If the session has no ids, then the db adapter must be used to retrieve them.
     * Then writes these ids to the session and sets the session state to "active".
     * For now we will write the ids as a serialized array.
     */
    public function startSession()
    {
        $ids = $this->preloadRecordIds()->getRecordIds();
        $type = $this->getType();

        $session = $this->getDataSession();

        //set type
        $session->setType($type);

        //set ids
        $session->setIds(serialize($ids));

        //set position
        $session->setPosition(0);

        //set count
        $session->setCount(count($ids));

        $dateTime = new \DateTime('now');

        //set date/time
        $session->setCreatedAt($dateTime);

        $fileName = $type . '-' . $dateTime->format('Y-m-d_H-i-s');

        //set count
        $session->setFileName($fileName);

        //set format
        $session->setFormat($this->getFormat());

        //change state
        $session->setState('active');

        Shopware()->Models()->persist($session);

        Shopware()->Models()->flush();
    }

    /**
     * Checks if the number of processed records has reached the current max records count.
     * If reached then the session state will be set to "stopped"
     * Updates the session position with the current position (stored in a member variable).
     *
     */
    public function progressSession()
    {
        //todo: get step ???
        $step = 100;

        $session = $this->getDataSession();

        $position = $session->getPosition();
        $count = $session->getCount();

        $newPosition = $position + $step;

        if ($newPosition >= $count) {
            $session->setState('finished');
            $session->setPosition($count);
        } else {
            $session->setPosition($newPosition);
        }

        Shopware()->Models()->persist($session);

        Shopware()->Models()->flush();
    }

    /**
     * Marks the session as closed (sets the session state as "closed").
     * If the session progress has not reached to the end, throws an exception.
     */
    public function closeSession()
    {
        $session = $this->getDataSession();
        $session->setState('closed');

        Shopware()->Models()->persist($session);

        Shopware()->Models()->flush();
    }

    /**
     * Checks also the current position - if all the ids of the session are done, then the function does nothing.
     * Otherwise it sets the session state from "suspended" to "active", so that it is ready again for processing.
     */
    public function resumeSession()
    {
        //todo: maybe check state before make it active ???
        $session = $this->getDataSession();

        $session->setState('active');

        Shopware()->Models()->persist($session);

        Shopware()->Models()->flush();
    }

    /**
     * Returns the max records count initialized in the constructor.
     */
    public function getMaxRecordsCount()
    {
        return $this->maxRecordCount;
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

    public function getDataSession()
    {
        return $this->dataSession;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Returns db columns
     * 
     * @return string
     */
    public function getColumns()
    {
        $colOptions = $this->getColumnOptionsAdapter()->getColumnOptions();

        if ($colOptions === null || empty($colOptions)) {
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

        $end = $start + $records;

        $filterIds = array();
        $counter = 0;

        foreach ($storedIds as $index => $id) {
            if ($index >= $start && $counter < $end) {
                $filterIds[] = $id;
                $counter ++;
            }
        }

        return implode(',', $filterIds);
    }

}
