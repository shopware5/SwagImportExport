<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Logger\LogDataStruct;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Utils\DataColumnOptions;
use SwagImportExport\Components\Utils\DataFilter;
use SwagImportExport\Components\Utils\DataLimit;
use SwagImportExport\Components\Utils\SnippetsHelper;

class DataIO
{
    private DataDbAdapter $dbAdapter;

    private DataColumnOptions $columnOptions;

    private DataLimit $limit;

    private DataFilter $filter;

    /**
     * Array of records ids
     */
    private array $recordIds;

    /**
     * Type of the dataIO - export/import
     */
    private ?string $type = null;

    /**
     * Format of the doc - csv, xml
     */
    private string $format;

    private string $fileName;

    private int $fileSize;

    private ?string $username = null;

    private Session $dataSession;

    private Logger $logger;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(DataDbAdapter $dbAdapter, Session $dataSession, Logger $logger, UploadPathProvider $uploadPathProvider)
    {
        $this->dbAdapter = $dbAdapter;
        $this->dataSession = $dataSession;
        $this->logger = $logger;
        $this->uploadPathProvider = $uploadPathProvider;
    }

    public function initialize(
        DataColumnOptions $colOpts,
        DataLimit $limit,
        DataFilter $filter,
        ?string $type,
        string $format
    ) {
        $this->columnOptions = $colOpts;
        $this->limit = $limit;
        $this->filter = $filter;
        $this->type = $type;
        $this->format = $format;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(int $numberOfRecords): array
    {
        $start = $this->getSessionPosition();

        $ids = $this->loadIds($start, $numberOfRecords);

        $columns = $this->getColumns();

        $dbAdapter = $this->getDbAdapter();

        return $dbAdapter->read($ids, $columns);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $defaults
     */
    public function write(array $data, array $defaults): void
    {
        $dbAdapter = $this->getDbAdapter();

        // Pass default values to adapter for further use
        if ($defaults) {
            $dbAdapter->setDefaultValues($defaults);
        }

        $dbAdapter->write($data);
    }

    public function writeLog(string $fileName, string $profileName): void
    {
        $dbAdapter = $this->getDbAdapter();

        $messages = $dbAdapter->getLogMessages();
        $state = $dbAdapter->getLogState();
        $status = isset($state) ? $state : 'false';

        if (empty($messages)) {
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
     * @return array<mixed>|null
     */
    public function getUnprocessedData(): ?array
    {
        $dbAdapter = $this->getDbAdapter();

        return $dbAdapter->getUnprocessedData();
    }

    public function preloadRecordIds(): self
    {
        $session = $this->dataSession;
        $storedIds = $session->getEntity()->getIds();

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
     */
    public function getSessionState(): string
    {
        return $this->dataSession->getState();
    }

    public function getSessionPosition(): int
    {
        $position = $this->dataSession->getEntity()->getPosition();

        return $position == null ? 0 : $position;
    }

    public function generateFileName(Profile $profile): string
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

    public function generateRandomHash(int $length): string
    {
        return \substr(\md5(\uniqid()), 0, $length);
    }

    public function getDirectory(): string
    {
        $directory = $this->uploadPathProvider->getPath();

        if (!\file_exists($directory)) {
            $this->createDirectory($directory);
        }

        return $directory;
    }

    public function createDirectory(string $path): void
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
     */
    public function startSession(Profile $profile): void
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
     */
    public function progressSession(int $step, string $outputFileName = null): void
    {
        $this->getDataSession()->progress($step, $outputFileName);
    }

    /**
     * Marks the session as closed (sets the session state as "closed").
     * If the session progress has not reached to the end, throws an exception.
     */
    public function closeSession(): void
    {
        $this->getDataSession()->close();
    }

    /**
     * Change username for current session
     */
    public function usernameSession(): void
    {
        $username = $this->getUsername();
        $this->getDataSession()->setUsername($username);
    }

    /**
     * Checks also the current position - if all the ids of the session are done, then the function does nothing.
     * Otherwise it sets the session state from "suspended" to "active", so that it is ready again for processing.
     */
    public function resumeSession(): void
    {
        $sessionData = $this->getDataSession()->resume();

        $this->setRecordIds($sessionData['recordIds']);

        $this->setFileName($sessionData['fileName']);
    }

    public function getDbAdapter(): DataDbAdapter
    {
        return $this->dbAdapter;
    }

    public function getColumnOptionsAdapter(): DataColumnOptions
    {
        return $this->columnOptions;
    }

    public function getLimitAdapter(): DataLimit
    {
        return $this->limit;
    }

    public function getFilterAdapter(): DataFilter
    {
        return $this->filter;
    }

    /**
     * @return array<int>
     */
    public function getRecordIds(): array
    {
        return $this->recordIds;
    }

    /**
     * @param array<int> $recordIds
     */
    public function setRecordIds(array $recordIds): void
    {
        $this->recordIds = $recordIds;
    }

    public function getDataSession(): Session
    {
        return $this->dataSession;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * Returns db columns
     *
     * @return array<int|string, array<string>|string>
     */
    public function getColumns(): array
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
     * @throws \Exception
     *
     * @return array<int>
     */
    private function loadIds(int $start, int $numberOfRecords): array
    {
        $storedIds = $this->getRecordIds();

        if (empty($storedIds)) {
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
