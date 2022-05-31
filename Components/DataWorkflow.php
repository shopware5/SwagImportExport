<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components;

use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\FileIO\FileWriter;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Transformers\DataTransformerChain;

class DataWorkflow
{
    protected ?DataIO $dataIO = null;

    protected Profile $profile;

    protected DataTransformerChain $transformerChain;

    /**
     * @var FileWriter|FileReader
     */
    protected object $fileIO;

    protected Session $dataSession;

    /**
     * @var
     */
    protected $dbAdapter;

    /**
     * @param FileWriter|FileReader $fileIO
     */
    public function __construct(?DataIO $dataIO, Profile $profile, DataTransformerChain $transformerChain, $fileIO)
    {
        $this->dataIO = $dataIO;
        $this->profile = $profile;
        $this->transformerChain = $transformerChain;
        $this->fileIO = $fileIO;
    }

    /**
     * @param array<string, mixed> $postData
     */
    public function export(array $postData, string $outputFileName = '')
    {
        if ($this->dataIO->getSessionState() === 'closed') {
            $postData['position'] = $this->dataIO->getSessionPosition();
            $postData['fileName'] = $this->dataIO->getDataSession()->getFileName();

            return $postData;
        }

        if ($this->dataIO->getSessionState() === 'new') {
            //todo: create file here ?
            if ($outputFileName === '') {
                $fileName = $this->dataIO->generateFileName($this->profile);
                $this->dataIO->getDirectory();
                $outputFileName = $this->getUploadPathProvider()->getRealPath($fileName);
            } else {
                $fileName = \basename($outputFileName);
                $this->dataIO->setFileName($fileName);
            }

            // session has no ids stored yet, therefore we must start it and write the file headers
            $header = $this->transformerChain->composeHeader();
            $this->fileIO->writeHeader($outputFileName, $header);
            $this->dataIO->startSession($this->profile);
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $this->dataIO->resumeSession();

            if ($outputFileName === '') {
                $uploadPathProvider = $this->getUploadPathProvider();
                $outputFileName = $uploadPathProvider->getRealPath($this->dataIO->getFileName());
            }
        }

        if ($this->dataIO->getSessionState() === 'active') {
            $stepSize = Shopware()->Config()->getByNamespace('SwagImportExport', 'batch-size-export');
            // read a bunch of records into simple php array;
            // the count of records may be less than 100 if we are at the end of the read.
            $data = $this->dataIO->read($stepSize);
            // process that array with the full transformation chain
            $data = $this->transformerChain->transformForward($data);

            // now the array should be a tree and we write it to the file
            $this->fileIO->writeRecords($outputFileName, $data);

            // writing is successful, so we write the new position in the session;
            // if if the new position goes above the limits provided by the
            $this->dataIO->progressSession($stepSize, $outputFileName);
        }

        if ($this->dataIO->getSessionState() === 'finished') {
            // Session finished means we have exported all the ids in the session.
            // Therefore we can close the file with a footer and mark the session as done.
            $footer = $this->transformerChain->composeFooter();
            $this->fileIO->writeFooter($outputFileName, $footer);
            $this->dataIO->closeSession();
        }

        $postData['position'] = $this->dataIO->getSessionPosition();

        if (!$postData['sessionId']) {
            $postData['sessionId'] = $this->dataIO->getDataSession()->getId();
        }

        if (!$postData['fileName']) {
            $postData['fileName'] = $fileName;
        }

        return $postData;
    }

    /**
     * @param array<string, mixed> $postData
     */
    public function import(array $postData, string $inputFile)
    {
        $tree = \json_decode($this->profile->getConfig('tree'), true);
        if ($postData['format'] === 'xml') {
            $this->fileIO->setTree($tree);
        }

        if ($this->dataIO->getSessionState() === 'new') {
            $totalCount = $this->fileIO->getTotalCount($inputFile);
            $this->dataIO->setFileName($postData['importFile']);
            $this->dataIO->setFileSize(\filesize($inputFile));
            $this->dataIO->getDataSession()->setTotalCount($totalCount);
            $this->dataIO->startSession($this->profile);
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $this->dataIO->resumeSession();
        }

        $this->dataIO->usernameSession();
        if ($this->dataIO->getSessionState() === 'active') {
            //get current session position
            $batchSize = (int) $postData['batchSize'];

            $position = $this->dataIO->getSessionPosition();

            $records = $this->fileIO->readRecords($inputFile, $position, $batchSize);

            $data = $this->transformerChain->transformBackward($records);

            $defaultValues = $this->profile->getDefaultValues($tree);

            //inserts/update data into the database
            $this->dataIO->write($data, $defaultValues);

            //writes into database log table
            $profileName = $this->profile->getName();
            $this->dataIO->writeLog($inputFile, $profileName);

            $this->dataIO->progressSession($batchSize);

            //gets unprocessed data from the adapter
            $postData['unprocessedData'] = $this->dataIO->getUnprocessedData();
        }

        if ($this->dataIO->getSessionState() === 'finished') {
            $this->dataIO->closeSession();
        }

        $postData['position'] = $this->dataIO->getSessionPosition();

        if (!$postData['sessionId']) {
            $postData['sessionId'] = $this->dataIO->getDataSession()->getId();
        }

        return $postData;
    }

    /**
     * @param array<string, mixed> $postData
     */
    public function saveUnprocessedData(array $postData, string $profileName, string $outputFile)
    {
        if ($postData['session']['prevState'] === 'new' || !\filesize($outputFile)) {
            $header = $this->transformerChain->composeHeader();
            $this->fileIO->writeHeader($outputFile, $header);
        }

        $data = $this->transformerChain->transformForward($postData['data'][$profileName]);

        $this->fileIO->writeRecords($outputFile, $data);
    }

    /**
     * @return UploadPathProvider
     */
    private function getUploadPathProvider()
    {
        /* @var UploadPathProvider $uploadPathProvider */
        return Shopware()->Container()->get('swag_import_export.upload_path_provider');
    }
}
