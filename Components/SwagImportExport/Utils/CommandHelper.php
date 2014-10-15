<?php

namespace Shopware\Components\SwagImportExport\Utils;

use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\StatusLogger;

class CommandHelper
{

    // required
    protected $profileEntity;
    protected $filePath;
    protected $format;
    
    // optional
    protected $exportVariants;
    protected $limit;
    protected $offset;
    protected $username;
    
    //private
    protected $sessionId;
    
    /**
     * Tries to find profile by given name
     * 
     * @param string $filename
     * @param \Doctrine\ORM\EntityRepository $repository
     * @return boolean
     */
    public static function findProfileByName($filename, $repository)
    {
        $parts = explode('.', $filename);
        foreach ($parts as $part) {
            $part = strtolower($part);
            $profileEntity = $repository->findOneBy(array('name' => $part));
            if ($profileEntity !== NULL) {
                return $profileEntity;
            }
        }
        
        return false;
    }
    
    /**
     * Construct
     * 
     * @param array $data
     */
    public function __construct(array $data)
    {
        // required
        if (isset($data['profileEntity'])) {
            $this->profileEntity = $data['profileEntity'];
        } else {
            throw new \Exception("No profile given!");
        }
        if (isset($data['filePath'])) {
            $this->filePath = $data['filePath'];
        } else {
            throw new \Exception("No filePath given!");
        }
        if (isset($data['format'])) {
            $this->format = $data['format'];
        } else {
            throw new \Exception("No format given!");
        }
        
        // optional
        if (isset($data['exportVariants'])) {
            $this->exportVariants = $data['exportVariants'];
        }
        if (isset($data['limit'])) {
            $this->limit = $data['limit'];
        }
        if (isset($data['offset'])) {
            $this->offset = $data['offset'];
        }
        if (isset($data['username'])) {
            $this->username = $data['username'];
        }
    }
    
    /**
     * Prepares export
     * 
     * @return array
     */
    public function prepareExport()
    {
        $this->sessionId = null;
        $postData = array(
            'sessionId' => $this->sessionId,
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'export',
            'format' => $this->format,
            'filter' => array(),
            'limit' => array(
                'limit' => $this->limit,
                'offset' => $this->offset,
            ),
        );

        if ($this->exportVariants) {
            $postData['filter']['variants'] = $this->exportVariants;
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $maxRecordCount, $type, $format);
        
        $ids = $dataIO->preloadRecordIds()->getRecordIds();

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        return array('position' => $position, 'count' => count($ids));
    }

    /**
     * Executes export action
     * 
     * @return array
     */
    public function exportAction()
    {
        $postData = array(
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'export',
            'format' => $this->format,
            'sessionId' => $this->sessionId,
            'fileName' => basename($this->filePath),
            'filter' => array(),
            'limit' => array(
                'limit' => $this->limit,
                'offset' => $this->offset,
            ),
        );

        if ($this->exportVariants) {
            $postData['filter']['variants'] = $this->exportVariants;
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($this->username);
        
        // we create the file writer that will write (partially) the result file
        $fileFactory = $this->Plugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter($postData, $fileHelper);

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        $post = $dataWorkflow->export($postData, $this->filePath);

        $this->sessionId = $post['sessionId'];
        return $post;
    }
    
    /**
     * Prepares import
     * 
     * @return array
     */
    public function prepareImport()
    {
        $this->sessionId = null;
        $postData = array(
            'sessionId' => $this->sessionId,
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'import',
            'format' => $this->format,
            'file' => $this->filePath,
        );

        //get file format
        $inputFileName = $postData['file'];

        //get profile type
        $postData['adapter'] = $this->profileEntity->getType();

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData, null);

        if ($this->format === 'xml') {
            $tree = json_decode($this->profileEntity->getTree(), true);
            $fileReader->setTree($tree);
        }

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($this->profileEntity->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $totalCount = $fileReader->getTotalCount($inputFileName);

        return array('success' => true, 'position' => $position, 'count' => $totalCount);
    }

    /**
     * Executes import action
     * 
     * @return array
     */
    public function importAction()
    {
        $postData = array(
            'type' => 'import',
            'profileId' => (int) $this->profileEntity->getId(),
            'importFile' => $this->filePath,
            'sessionId' => $this->sessionId,
            'format' => $this->format,
            'columnOptions' => null,
            'limit' => array(),
            'filter' => null,
            'max_record_count' => null,
        );

        $inputFile = $postData['importFile'];

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData, null);

        //load profile
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        //setting up the batch size
        $postData['batchSize'] = $profile->getType() === 'articlesImages' ? 1 : 50;
        
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($this->username);
        
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileReader->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);
        $logger = new StatusLogger();
        
        try {
            $post = $dataWorkflow->import($postData, $inputFile);
            
            $this->sessionId = $post['sessionId'];
            if ($dataSession->getTotalCount() > 0 && ($dataSession->getTotalCount() == $post['position'])) {
                $message = $post['position'] . ' ' . $post['adapter'] . ' imported successfully';
                $logger->write($message, 'false');
            }
            
            return array('success' => true, 'data' => $post);
        } catch (Exception $e) {
            $logger->write($e->getMessage(), 'true');
            
            return array('success' => false, 'msg' => $e->getMessage());
        }
    }
    
    protected function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

}
