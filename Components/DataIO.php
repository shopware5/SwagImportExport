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
use SwagImportExport\Components\DbAdapters\DefaultHandleable;
use SwagImportExport\Components\Logger\LogDataStruct;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\Utils\SnippetsHelper;

class DataIO
{
    private DataDbAdapter $dbAdapter;

    private Session $dataSession;

    private Logger $logger;

    public function __construct(DataDbAdapter $dbAdapter, Session $dataSession, Logger $logger)
    {
        $this->dbAdapter = $dbAdapter;
        $this->dataSession = $dataSession;
        $this->logger = $logger;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(ExportRequest $request): array
    {
        $start = $this->dataSession->getPosition();

        $ids = $this->loadIdsFromSession($start, $request->batchSize);

        $columns = $this->getColumns($request);

        return $this->dbAdapter->read($ids, $columns);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $defaults
     */
    public function write(array $data, array $defaults): void
    {
        if ($this->dbAdapter instanceof DefaultHandleable) {
            $this->dbAdapter->setDefaultValues($defaults);
        }

        $this->dbAdapter->write($data);
    }

    public function writeLog(string $fileName, string $profileName): void
    {
        $messages = $this->dbAdapter->getLogMessages();
        $state = $this->dbAdapter->getLogState();
        $status = isset($state) ? $state : 'false';

        if (empty($messages)) {
            return;
        }

        $this->logger->write($messages, $status, $this->dataSession);

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
     * @return array<mixed>
     */
    public function getUnprocessedData(): array
    {
        return $this->dbAdapter->getUnprocessedData();
    }

    /**
     * @return array<int>
     */
    public function preloadRecordIds(ExportRequest $exportRequest, Session $session): array
    {
        $ids = $this->dbAdapter->readRecordIds(
            $exportRequest->offset,
            $exportRequest->limit,
            $exportRequest->filter
        );

        $session->setRecordIds($ids);

        return $ids;
    }

    /**
     * Returns db columns
     *
     * @return array<int|string, array<string>|string>
     */
    private function getColumns(ExportRequest $request): array
    {
        if (empty($request->columnOptions)) {
            return $this->dbAdapter->getDefaultColumns();
        }

        return $request->columnOptions;
    }

    /**
     * Returns number of ids
     *
     * @throws \Exception
     *
     * @return array<int>
     */
    private function loadIdsFromSession(int $start, int $numberOfRecords): array
    {
        $storedIds = $this->dataSession->getRecordIds();

        if (empty($storedIds)) {
            $message = SnippetsHelper::getNamespace()->get('dataio/no_loaded_records', 'No loaded record ids');
            throw new \Exception($message);
        }

        return \array_slice($storedIds, $start, $numberOfRecords, true);
    }
}
