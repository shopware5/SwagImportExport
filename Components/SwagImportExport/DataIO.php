<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\Logger\LogDataStruct;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\Session\Session;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class DataIO
{
    /**
     * @var DataDbAdapter
     */
    private $dbAdapter;

    /**
     * @var DataColumnOptions
     */
    private $columnOptions;

    /**
     * @var DataLimit
     */
    private $limit;

    /**
     * @var DataFilter
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
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $fileSize;

    /**
     * Username made the action
     *
     * @var string
     */
    private $username;

    /**
     * @var Session
     */
    private $dataSession;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UploadPathProvider
     */
    private $uploadPathProvider;

    /**
     * @param Session $dataSession
     * @param Logger  $logger
     */
    public function __construct(DataDbAdapter $dbAdapter, $dataSession, $logger, UploadPathProvider $uploadPathProvider)
    {
        $this->dbAdapter = $dbAdapter;
        $this->dataSession = $dataSession;
        $this->logger = $logger;
        $this->uploadPathProvider = $uploadPathProvider;
    }

    public function initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount)
    {
        $this->columnOptions = $colOpts;
        $this->limit = $limit;
        $this->filter = $filter;
        $this->type = $type;
        $this->format = $format;
        $this->maxRecordCount = $maxRecordCount;
    }

    /**
     * @param int $numberOfRecords
     */
    public function read($numberOfRecords)
    {
        $start = $this->getSessionPosition();

        $ids = $this->loadIds($start, $numberOfRecords);

        $columns = $this->getColumns();

        $dbAdapter = $this->getDbAdapter();

        return $dbAdapter->read($ids, $columns);
    }

    public function write($data, $defaults)
    {
        $dbAdapter = $this->getDbAdapter();

        //Pass default values to adapter for further use
        if ($defaults) {
            $dbAdapter->setDefaultValues($defaults);
        }

        $dbAdapter->write($data);
    }

    /**
     * @param string $fileName
     * @param string $profileName
     */
    public function writeLog($fileName, $profileName)
    {
        $dbAdapter = $this->getDbAdapter();

        $messages = $dbAdapter->getLogMessages();
        $state = $dbAdapter->getLogState();
        $status = isset($state) ? $state : 'false';

        if (!$messages || empty($messages)) {
            return;
        }

        $this->logger->write($messages, $status, $this->dataSession->getEntity());

        $logDataStruct = new LogDataStruct(
            \date('Y-m-d H:i:s'),
            $fileName,
            $profileName,
            \implode("\n", $messages),
            $status
        );

        $this->logger->writeToFile($logDataStruct);
    }

    /**
     * @return array|null
     */
    public function getUnprocessedData()
    {
        $dbAdapter = $this->getDbAdapter();

        return $dbAdapter->getUnprocessedData();
    }

    /**
     * Loads the record ids
     *
     * @return \Shopware\Components\SwagImportExport\DataIO
     */
    public function preloadRecordIds()
    {
        $session = $this->dataSession;
        $storedIds = $session->getIds();

        if ($storedIds) {
            $ids = \unserialize($storedIds);
        } else {
            $dbAdapter = $this->getDbAdapter();
            $limitAdapter = $this->getLimitAdapter();
            $filterAdapter = $this->getFilterAdapter();

            $ids = $dbAdapter->readRecordIds(
                $limitAdapter->getOffset(),
                $limitAdapter->getLimit(),
                $filterAdapter->getFilter()
            );
        }

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
     *
     * @return string
     */
    public function getSessionState()
    {
        return $this->dataSession->getState();
    }

    /**
     * @return int
     */
    public function getSessionPosition()
    {
        $position = $this->dataSession->getPosition();

        return $position == null ? 0 : $position;
    }

    /**
     * Generates file name
     *
     * @return string
     */
    public function generateFileName(Profile $profile)
    {
        $operationType = $this->getType();
        $fileFormat = $this->getFormat();

        $adapterType = $profile->getType();

        $hash = $this->generateRandomHash(8);

        $dateTime = new \DateTime('now');

        $fileName = $operationType . '.'
            . $adapterType . '.' . $dateTime->format('Y.m.d.h.i.s')
            . '-' . $hash . '.' . $fileFormat;

        $this->setFileName($fileName);

        return $fileName;
    }

    /**
     * Generates random hash depends on the length
     *
     * @param int $length
     *
     * @return string
     */
    public function generateRandomHash($length)
    {
        return \substr(\md5(\uniqid()), 0, $length);
    }

    /**
     * Returns directory of the import/export plugin
     *
     * @return string
     */
    public function getDirectory()
    {
        $directory = $this->uploadPathProvider->getPath();

        if (!\file_exists($directory)) {
            $this->createDirectory($directory);
        }

        return $directory;
    }

    /**
     * Creates directory
     *
     * @param string $path
     *
     * @throws \Exception
     */
    public function createDirectory($path)
    {
        if (!\mkdir($path, 0777, true)) {
            $message = SnippetsHelper::getNamespace()->get('dataio/no_profile', 'Failed to create directory %s');
            throw new \Exception(\sprintf($message, $path));
        }
    }

    /**
     * Check if the session contains ids.
     * If the session has no ids, then the db adapter must be used to retrieve them.
     * Then writes these ids to the session and sets the session state to "active".
     * For now we will write the ids as a serialized array.
     *
     * @throws \Exception
     */
    public function startSession(Profile $profile)
    {
        $sessionData = [
            'type' => $this->getType(),
            'fileName' => $this->getFileName(),
            'format' => $this->getFormat(),
        ];

        $session = $this->getDataSession();

        switch ($sessionData['type']) {
            case 'export':
                $ids = $this->preloadRecordIds()->getRecordIds();

                if (empty($ids)) {
                    $message = SnippetsHelper::getNamespace()
                        ->get('dataio/no_export_records', 'No records found to be exported');
                    throw new \Exception($message);
                }

                $sessionData['serializedIds'] = \serialize($ids);
                $sessionData['totalCountedIds'] = \count($ids);
                $sessionData['username'] = $this->getUsername();
                break;
            case 'import':
                $sessionData['serializedIds'] = '';
                $sessionData['username'] = $this->getUsername();
                $sessionData['fileSize'] = $this->getFileSize();
                break;

            default:
                $message = SnippetsHelper::getNamespace()
                    ->get('dataio/session_type_not_valid', 'Session type %s is not valid');
                throw new \Exception(\sprintf($message, $sessionData['type']));
        }

        $session->start($profile, $sessionData);
    }

    /**
     * Checks if the number of processed records has reached the current max records count.
     * If reached then the session state will be set to "stopped"
     * Updates the session position with the current position (stored in a member variable).
     *
     * @param null $outputFileName
     */
    public function progressSession($step, $outputFileName = null)
    {
        $this->getDataSession()->progress($step, $outputFileName);
    }

    /**
     * Marks the session as closed (sets the session state as "closed").
     * If the session progress has not reached to the end, throws an exception.
     */
    public function closeSession()
    {
        $this->getDataSession()->close();
    }

    /**
     * Change username for current session
     */
    public function usernameSession()
    {
        $username = $this->getUsername();
        $this->getDataSession()->setUsername($username);
    }

    /**
     * Checks also the current position - if all the ids of the session are done, then the function does nothing.
     * Otherwise it sets the session state from "suspended" to "active", so that it is ready again for processing.
     */
    public function resumeSession()
    {
        $sessionData = $this->getDataSession()->resume();

        $this->setRecordIds($sessionData['recordIds']);

        $this->setFileName($sessionData['fileName']);
    }

    public function getSessionId()
    {
        $session = $this->getDataSession();

        return $session->getId();
    }

    /**
     * Returns the max records count initialized in the constructor.
     */
    public function getMaxRecordsCount()
    {
        return $this->maxRecordCount;
    }

    /**
     * @return DataDbAdapter
     */
    public function getDbAdapter()
    {
        return $this->dbAdapter;
    }

    /**
     * @return DataColumnOptions
     */
    public function getColumnOptionsAdapter()
    {
        return $this->columnOptions;
    }

    /**
     * @return DataLimit
     */
    public function getLimitAdapter()
    {
        return $this->limit;
    }

    /**
     * @return DataFilter
     */
    public function getFilterAdapter()
    {
        return $this->filter;
    }

    /**
     * @return array
     */
    public function getRecordIds()
    {
        return $this->recordIds;
    }

    public function setRecordIds($recordIds)
    {
        $this->recordIds = $recordIds;
    }

    /**
     * @return Session
     */
    public function getDataSession()
    {
        return $this->dataSession;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Returns db columns
     *
     * @return array
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
     * @param int $numberOfRecords
     *
     * @throws \Exception
     *
     * @return array
     */
    private function loadIds($start, $numberOfRecords)
    {
        $storedIds = $this->getRecordIds();

        if ($storedIds === null || empty($storedIds)) {
            $message = SnippetsHelper::getNamespace()->get('dataio/no_loaded_records', 'No loaded record ids');
            throw new \Exception($message);
        }

        $end = $start + $numberOfRecords;
        $filterIds = [];

        for ($index = $start; $index < $end; ++$index) {
            if (isset($storedIds[$index])) {
                $filterIds[] = $storedIds[$index];
            }
        }

        return $filterIds;
    }
}
