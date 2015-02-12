<?php

namespace Shopware\Components\SwagImportExport;

use Shopware\Components\SwagImportExport\FileIO\FileWriter;
use \Shopware\Components\SwagImportExport\Profile\Profile;

class DataWorkflow
{

    /**
     * @var \Shopware\Components\SwagImportExport\DataIO
     */
    protected $dataIO;

    /**
     * @var \Shopware\Components\SwagImportExport\Profile\Profile 
     */
    protected $profile;

    /**
     * @var \Shopware\Components\SwagImportExport\Transoformers\DataTransformerChain
     */
    protected $transformerChain;

    /**
     * @var FileWriter $fileIO
     */
    protected $fileIO;

    /**
     * @var \Shopware\Components\SwagImportExport\Session\Session
     */
    protected $dataSession;

    /**
     * @var $dbAdapter 
     */
    protected $dbAdapter;

    /**
     * @param DataIO $dataIO
     * @param Profile $profile
     * @param type $transformerChain
     * @param type $fileIO
     */
    public function __construct($dataIO, $profile, $transformerChain, $fileIO)
    {
        $this->dataIO = $dataIO;
        $this->profile = $profile;
        $this->transformerChain = $transformerChain;
        $this->fileIO = $fileIO;
    }

    public function export($postData, $outputFileName = '')
    {
        if ($this->dataIO->getSessionState() == 'closed') {
            $postData['position'] = $this->dataIO->getSessionPosition();
            $postData['fileName'] = $this->dataIO->getDataSession()->getFileName();

            return $postData;
        }

        if ($this->dataIO->getSessionState() == 'new') {
            //todo: create file here ?
            if ($outputFileName === '') {
                $fileName = $this->dataIO->generateFileName($this->profile);
                $directory = $this->dataIO->getDirectory();
            
                $outputFileName = $directory . $fileName;
            } else {
                $fileName = basename($outputFileName);
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
                $outputFileName = Shopware()->DocPath() . 'files/import_export/' . $this->dataIO->getFileName();
            }
        }
        
        if ($this->dataIO->getSessionState() == 'active') {
            $stepSize = 1000;
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
        
        if ($this->dataIO->getSessionState() == 'finished') {
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

    public function import($postData, $inputFile)
    {
        if ($postData['format'] === 'xml') {
            $tree = json_decode($this->profile->getConfig("tree"), true);
            $this->fileIO->setTree($tree);
        }
        
        if ($this->dataIO->getSessionState() == 'new') {
            $totalCount = $this->fileIO->getTotalCount($inputFile);
            $this->dataIO->setFileName($postData['importFile']);
            $this->dataIO->setFileSize(filesize($inputFile));
            $this->dataIO->getDataSession()->setTotalCount($totalCount);            
            $this->dataIO->startSession($this->profile);
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $this->dataIO->resumeSession();
        }
        $this->dataIO->usernameSession();
        if ($this->dataIO->getSessionState() == 'active') {
            //get current session position
            $batchSize = (int) $postData['batchSize'];

            $position = $this->dataIO->getSessionPosition();

            $records = $this->fileIO->readRecords($inputFile, $position, $batchSize);

            $data = $this->transformerChain->transformBackward($records);

            //inserts/update data into the database
            $this->dataIO->write($data);

            //writes into database log table
            $profileName = $this->profile->getName();
            $this->dataIO->writeLog($inputFile, $profileName);

            $this->dataIO->progressSession($batchSize);

            //gets unprocessed data from the adapter
            $postData['unprocessedData'] = $this->dataIO->getUnprocessedData();

        }

        if ($this->dataIO->getSessionState() == 'finished') {
            $this->dataIO->closeSession();
        }

        $postData['position'] = $this->dataIO->getSessionPosition();

        if (!$postData['sessionId']) {
            $postData['sessionId'] = $this->dataIO->getDataSession()->getId();
        }
        
        return $postData;
    }

    public function saveUnprocessedData($postData, $outputFile)
    {
        if ($postData['session']['prevState'] === 'new') {
            $header = $this->transformerChain->composeHeader();
            $this->fileIO->writeHeader($outputFile, $header);
        }

        $data = $this->transformerChain->transformForward($postData['data']);

        $this->fileIO->writeRecords($outputFile, $data);
    }
}
