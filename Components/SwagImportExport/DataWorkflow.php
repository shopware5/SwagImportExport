<?php

namespace Shopware\Components\SwagImportExport;

use \Shopware\Components\SwagImportExport\Profile\Profile;

class DataWorkflow
{

    /**
     * @var Shopware\Components\SwagImportExport\DataIO
     */
    protected $dataIO;

    /**
     * @var \Shopware\Components\SwagImportExport\Profile\Profile 
     */
    protected $profile;

    /**
     * @var Shopware\Components\SwagImportExport\Transoformers\DataTransformerChain 
     */
    protected $transformerChain;

    /**
     * @var $fileIO 
     */
    protected $fileIO;

    /**
     * @var Shopware\Components\SwagImportExport\Session\Session 
     */
    protected $dataSession;

    /**
     * @var $dbAdapter 
     */
    protected $dbAdapter;

    /**
     * @param DataIO $dataIO
     * @param type $profile
     * @param type $transformerChain
     * @param type $fileIO
     * @param type $dataSession
     * @param type $dbAdapter
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
            // read a bunch of records into simple php array;
            // the count of records may be less than 100 if we are at the end of the read.
            $data = $this->dataIO->read(1000);
            
            // process that array with the full transformation chain
            $data = $this->transformerChain->transformForward($data);
            
            // now the array should be a tree and we write it to the file
            $this->fileIO->writeRecords($outputFileName, $data);

            // writing is successful, so we write the new position in the session;
            // if if the new position goes above the limits provided by the 
            $this->dataIO->progressSession(1000);
        }
        
        if ($this->dataIO->getSessionState() == 'finished') {
            // Session finished means we have exported all the ids in the sesssion.
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
            $this->dataIO->getDataSession()->setTotalCount($totalCount);            
            $this->dataIO->startSession($this->profile);
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $this->dataIO->resumeSession();
        }

        if ($this->dataIO->getSessionState() == 'active') {
            //get current session position
            $position = $this->dataIO->getSessionPosition();

            $records = $this->fileIO->readRecords($inputFile, $position, 1);

            $data = $this->transformerChain->transformBackward($records);
            
            $this->dataIO->write($data);
            
            $this->dataIO->progressSession(1);
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

}
