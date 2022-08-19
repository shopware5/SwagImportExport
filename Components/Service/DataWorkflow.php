<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\DataIO;
use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Components\Providers\FileIOProvider;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\Structs\ImportRequest;

class DataWorkflow
{
    /**
     * @var array<string>|array<string, mixed>
     */
    private array $defaultValues = [];

    private SessionService $sessionService;

    private DataProvider $dataProvider;

    private DataTransformerFactory $dataTransformerFactory;

    private FileIOProvider $fileIOProvider;

    private Logger $logger;

    private ProfileFactory $profileFactory;

    public function __construct(
        DataProvider $provider,
        DataTransformerFactory $dataTransformerFactory,
        FileIOProvider $fileIOProvider,
        SessionService $sessionService,
        Logger $logger,
        ProfileFactory $profileFactory
    ) {
        $this->sessionService = $sessionService;
        $this->dataProvider = $provider;
        $this->dataTransformerFactory = $dataTransformerFactory;
        $this->fileIOProvider = $fileIOProvider;
        $this->logger = $logger;
        $this->profileFactory = $profileFactory;
    }

    public function export(ExportRequest $exportRequest, Session $session): int
    {
        $dbAdapter = $this->dataProvider->createDbAdapter($exportRequest->profileEntity->getType());
        $dataIo = new DataIO($dbAdapter, $session, $this->logger);
        $fileWriter = $this->fileIOProvider->getFileWriter($exportRequest->format);

        $transformerChain = $this->dataTransformerFactory->createDataTransformerChain(
            $exportRequest->profileEntity,
            ['isTree' => $fileWriter->hasTreeStructure()]
        );

        if ($session->getState() === Session::SESSION_CLOSE) {
            throw new \Exception(sprintf('Session is already closed. The results are in the file %s.', $session->getFileName()));
        }

        if ($session->getState() === Session::SESSION_NEW) {
            // session has no ids stored yet, therefore we must start it and write the file headers
            $header = $transformerChain->composeHeader();
            $fileWriter->writeHeader($exportRequest->filePath, $header);

            if (empty($session->getRecordIds())) {
                $dataIo->preloadRecordIds($exportRequest, $session);
            }

            $this->sessionService->startExportSession($exportRequest, $exportRequest->profileEntity, $session, $session->getRecordIds());
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $session->resume();
        }

        if ($session->getState() === Session::SESSION_ACTIVE) {
            // read a bunch of records into simple php array;
            // the count of records may be less than 100 if we are at the end of the read.
            $data = $dataIo->read($exportRequest);

            // process that array with the full transformation chain
            $data = $transformerChain->transformForward($data);

            // now the array should be a tree, and we write it to the file
            $fileWriter->writeRecords($exportRequest->filePath, $data);

            // writing is successful, so we write the new position in the session;
            // if the new position goes above the limits provided by the
            $session->progress($exportRequest->batchSize, $exportRequest->filePath);
        }

        if ($session->getState() === Session::SESSION_FINISHED) {
            // Session finished means we have exported all the ids in the session.
            // Therefore, we can close the file with a footer and mark the session as done.
            $footer = $transformerChain->composeFooter();
            $fileWriter->writeFooter($exportRequest->filePath, $footer);
            $session->close();
        }

        return $session->getPosition();
    }

    /**
     * @return array<string, mixed>
     */
    public function import(ImportRequest $request, Session $session): array
    {
        $adapter = $this->dataProvider->createDbAdapter($request->profileEntity->getType());
        $dataIo = new DataIO($adapter, $session, $this->logger);
        $fileReader = $this->fileIOProvider->getFileReader($request->format);

        $transformerChain = $this->dataTransformerFactory->createDataTransformerChain(
            $request->profileEntity,
            ['isTree' => $fileReader->hasTreeStructure()]
        );

        $tree = \json_decode($request->profileEntity->getEntity()->getTree(), true);
        if ($request->format === 'xml') {
            $fileReader->setTree($tree);
        }

        if ($session->getState() === Session::SESSION_NEW) {
            $totalCount = $fileReader->getTotalCount($request->inputFile);
            $session->setTotalCount($totalCount);
            $this->sessionService->startImportSession($request, $request->profileEntity, $session, \filesize($request->inputFile));
        }

        if ($session->getState() === Session::SESSION_ACTIVE) {
            // get current session position
            $batchSize = (int) $request->batchSize;

            $position = $session->getPosition();

            $records = $fileReader->readRecords($request->inputFile, $position, $batchSize);

            $data = $transformerChain->transformBackward($records);

            $this->defaultValues = [];
            $defaultValues = $this->getDefaultFields($tree);

            // inserts/update data into the database
            $dataIo->write($data, $defaultValues);

            // writes into database log table
            $profileName = $request->profileEntity->getName();
            $dataIo->writeLog($request->inputFile, $profileName);

            $session->progress($batchSize);

            if ($dataIo->supportsUnprocessedData()) {
                // gets unprocessed data from the adapter
                $postData['unprocessedData'] = $dataIo->getUnprocessedData();
            }
        }

        if ($session->getState() === Session::SESSION_FINISHED) {
            $session->close();
        }

        $postData['position'] = $session->getPosition();
        $postData['adapter'] = $request->profileEntity->getName();

        return $postData;
    }

    /**
     * @param array<string, mixed> $postData
     */
    public function saveUnprocessedData(array $postData, string $profileName, string $outputFile, string $prevState): void
    {
        $writer = $this->fileIOProvider->getFileWriter('csv');
        $profile = $this->profileFactory->loadHiddenProfile($profileName);

        $transformerChain = $this->dataTransformerFactory->createDataTransformerChain(
            $profile,
            ['isTree' => $writer->hasTreeStructure()]
        );

        if ($prevState === Session::SESSION_NEW || !\filesize($outputFile)) {
            $header = $transformerChain->composeHeader();
            $writer->writeHeader($outputFile, $header);
        }

        $data = $transformerChain->transformForward($postData[$profileName]);

        $writer->writeRecords($outputFile, $data);
    }

    /**
     * Check if current node have default value
     *
     * @param array<string|int , mixed> $node
     *
     * @return array<string>|array<string, array<string>>
     */
    private function getDefaultFields(array $node): array
    {
        if ($node) {
            foreach ($node['children'] as $leaf) {
                if (isset($leaf['children'])) {
                    $this->getDefaultFields($leaf);
                }

                if (!empty($leaf['defaultValue'])) {
                    $this->defaultValues[$leaf['shopwareField']] = $leaf['defaultValue'];
                }
            }
        }

        return $this->defaultValues;
    }
}
