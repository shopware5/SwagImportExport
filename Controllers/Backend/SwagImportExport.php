<?php

/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\Utils\ProfileComparator;
use Shopware\Components\SwagImportExport\Utils\TreeHelper;
use Shopware\Components\SwagImportExport\Utils\DataHelper;
use Shopware\Components\SwagImportExport\StatusLogger;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImageEditor
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExport extends Shopware_Controllers_Backend_ExtJs
{

    /**
     * Contains the shopware model manager
     *
     * @var Shopware\Components\Model\ModelManager
     */
    protected $manager;

    /**
     * @var Shopware\CustomModels\ImportExport\Profile
     */
    protected $profileRepository;

    /**
     * @var Shopware\CustomModels\ImportExport\Session
     */
    protected $sessionRepository;

    /**
     * @var Shopware\CustomModels\ImportExport\Expression
     */
    protected $expressionRepository;

    /**
     * 
     * @var Shopware\CustomModels\ImportExport\Logger
     */
    protected $loggerRepository;
        
    public function getProfileAction()
    {
        $profileId = $this->Request()->getParam('profileId', -1);
        
        if ($profileId === -1) {
            $this->View()->assign(array('success' => false, 'children' => array()));
            return;
        }
        
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));

        $tree = $profileEntity->getTree();
        $root = TreeHelper::convertToExtJSTree(json_decode($tree, 1));

        $this->View()->assign(array('success' => true, 'children' => $root));
    }

    public function createNodeAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));

        $tree = json_decode($profileEntity->getTree(), 1);

        if (isset($data['parentId'])) {
            $data = array($data);
        }

        $errors = false;
        
        foreach ($data as &$node) {
            $node['id'] = uniqid();
            if (!TreeHelper::appendNode($node, $tree)) {
                $errors = true;
            }
        }

        $profileEntity->setTree(json_encode($tree));

        $this->getManager()->persist($profileEntity);
        $this->getManager()->flush();

        if ($errors) {
            $this->View()->assign(array('success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data));
        } else {
            $this->View()->assign(array('success' => true, 'children' => $data));
        }
    }

    public function updateNodeAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));

        $tree = json_decode($profileEntity->getTree(), 1);

        if (isset($data['parentId'])) {
            $data = array($data);
        }

        $errors = false;
        
        foreach ($data as &$node) {
            if (!TreeHelper::changeNode($node, $tree)) {
                $errors = true;
                break;
            }
            
            // the root cannot be moved or deleted
            if ($node['id'] == 'root') {
                continue;
            }
            
            $changedNode = TreeHelper::getNodeById($node['id'], $tree);
            
            if ($node['parentId'] != $changedNode['parentId']) {
                $changedNode['parentId'] = $node['parentId'];
                $changedNode['index'] = $node['index'];
                $changedNode['type'] = $node['type'];
                if (!TreeHelper::deleteNode($node, $tree)) {
                    $errors = true;
                    break;
                } else if (!TreeHelper::moveNode($changedNode, $tree)) {
                    $errors = true;
                    break;
                }
            }
        }
        $reorderedTree = TreeHelper::reorderTree($tree);

        $profileEntity->setTree(json_encode($reorderedTree));

        $this->getManager()->persist($profileEntity);
        $this->getManager()->flush();

        if ($errors) {
            $this->View()->assign(array('success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data));
        } else {
            $this->View()->assign(array('success' => true, 'children' => $data));
        }
    }

    public function deleteNodeAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));

        $tree = json_decode($profileEntity->getTree(), 1);

        if (isset($data['parentId'])) {
            $data = array($data);
        }

        $errors = false;

        foreach ($data as &$node) {
            if (!TreeHelper::deleteNode($node, $tree)) {
                $errors = true;
            }
        }

        $profileEntity->setTree(json_encode($tree));

        $this->getManager()->persist($profileEntity);
        $this->getManager()->flush();

        if ($errors) {
            $this->View()->assign(array('success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data));
        } else {
            $this->View()->assign(array('success' => true, 'children' => $data));
        }
    }

    /**
     * Returns the new profile
     */
    public function createProfilesAction()
    {
        $data = $this->Request()->getParam('data', 1);

        try {

            $profileModel = $this->Plugin()->getProfileFactory()->createProfileModel($data);

            $this->View()->assign(array(
                'success' => true,
                'data' => array(
                    "id" => $profileModel->getId(),
                    'name' => $profileModel->getName(),
                    'type' => $profileModel->getType(),
                    'tree' => $profileModel->getTree(),
                )
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
        }
    }

    /**
     * Returns the new profile
     */
    public function duplicateProfileAction()
    {
        $profileId = $this->Request()->getParam('profileId');
        
        try {
            $loadedProfile = $this->getManager()->find('Shopware\CustomModels\ImportExport\Profile', (int) $profileId);
            
            if (!$loadedProfile) {
                throw new \Exception(sprintf('Profile with id %s does NOT exists', $profileId));
            }
                     
            $profile = new \Shopware\CustomModels\ImportExport\Profile();

            $profile->setName($loadedProfile->getName() . ' (copy)');
            $profile->setType($loadedProfile->getType());
            $profile->setTree($loadedProfile->getTree());

            $this->getManager()->persist($profile);
            $this->getManager()->flush();

            $this->View()->assign(array(
                'success' => true,
                'data' => array(
                    "id" => $profile->getId(),
                    'name' => $profile->getName(),
                    'type' => $profile->getType(),
                    'tree' => $profile->getTree(),
                )
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
        }
    }

    /**
     * Returns the new profile
     */
    public function updateProfilesAction()
    {
        $data = $this->Request()->getParam('data', 1);
        
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $data['id']));
        
        try {
            if (!$profileEntity) {
                throw new \Exception("Profile not found!");
            }
            
            $profileEntity->setName($data['name']);

            $this->getManager()->persist($profileEntity);
            $this->getManager()->flush();

            $this->View()->assign(array(
                'success' => true,
                'data' => array(
                    "id" => $profileEntity->getId(),
                    'name' => $profileEntity->getName(),
                    'type' => $profileEntity->getType(),
                    'tree' => $profileEntity->getTree(),
                )
            ));
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
        }
    }

    /**
     * Returns all profiles into an array
     */
    public function getProfilesAction()
    {
        $profileRepository = $this->getProfileRepository();

        $query = $profileRepository->getProfilesListQuery(
            $this->Request()->getParam('filter', array('hidden' => 0)),
            $this->Request()->getParam('sort', array()),
            $this->Request()->getParam('limit', null),
            $this->Request()->getParam('start')
        )->getQuery();

        $count = $this->getManager()->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }

    public function deleteProfilesAction()
    {
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = array($data);
        }

        try {
            $profileRepository = $this->getProfileRepository();
            foreach ($data as $profile) {
                $profileEntity = $profileRepository->findOneBy(array('id' => $profile['id']));
                $this->getManager()->remove($profileEntity);
            }
            $this->getManager()->flush();
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'msg' => 'Unexpected error. The profile could not be deleted.', 'children' => $data));
        }
        $this->View()->assign(array('success' => true));
    }

    public function getConversionsAction()
    {
        $profileId = $this->Request()->getParam('profileId');
        $filter = $this->Request()->getParam('filter', array());

        $expressionRepository = $this->getExpressionRepository();

        $filter = array_merge(array('p.id' => $profileId), $filter);

        $query = $expressionRepository->getExpressionsListQuery(
                        $filter, $this->Request()->getParam('sort', array()), $this->Request()->getParam('limit', null), $this->Request()->getParam('start')
                )->getQuery();

        $count = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }

    public function createConversionAction()
    {
        $profileId = $this->Request()->getParam('profileId');
        $data = $this->Request()->getParam('data', 1);

        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));

        $expressionEntity = new \Shopware\CustomModels\ImportExport\Expression();

        $expressionEntity->setProfile($profileEntity);
        $expressionEntity->setVariable($data['variable']);
        $expressionEntity->setExportConversion($data['exportConversion']);
        $expressionEntity->setImportConversion($data['importConversion']);

        Shopware()->Models()->persist($expressionEntity);
        Shopware()->Models()->flush();

        $this->View()->assign(array(
            'success' => true,
            'data' => array(
                "id" => $expressionEntity->getId(),
                'profileId' => $expressionEntity->getProfile()->getId(),
                'exportConversion' => $expressionEntity->getExportConversion(),
                'importConversion' => $expressionEntity->getImportConversion(),
            )
        ));
    }

    public function updateConversionAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = array($data);
        }

        $expressionRepository = $this->getExpressionRepository();

        try {
            foreach ($data as $expression) {
                $expressionEntity = $expressionRepository->findOneBy(array('id' => $expression['id']));
                $expressionEntity->setVariable($expression['variable']);
                $expressionEntity->setExportConversion($expression['exportConversion']);
                $expressionEntity->setImportConversion($expression['importConversion']);
                Shopware()->Models()->persist($expressionEntity);
            }

            Shopware()->Models()->flush();

            $this->View()->assign(array('success' => true, 'data' => $data));
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage(), 'data' => $data));
        }
    }

    public function deleteConversionAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = array($data);
        }

        $expressionRepository = $this->getExpressionRepository();

        try {
            foreach ($data as $expression) {
                $expressionEntity = $expressionRepository->findOneBy(array('id' => $expression['id']));
                Shopware()->Models()->remove($expressionEntity);
            }

            Shopware()->Models()->flush();

            $this->View()->assign(array('success' => true, 'data' => $data));
        } catch (\Exception $e) {
            $this->View()->assign(array('success' => false, 'message' => $e->getMessage(), 'data' => $data));
        }
    }

    public function prepareExportAction()
    {
        if ($this->Request()->getParam('limit')) {
            $limit = $this->Request()->getParam('limit');
        }
        
        if ($this->Request()->getParam('offset')) {
            $offset = $this->Request()->getParam('offset');
        }

        $postData = array(
            'sessionId' => $this->Request()->getParam('sessionId'),
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format'),
            'filter' =>  array(),
            'limit' =>  array(
                'limit' => $limit,
                'offset' => $offset,
            ),
        );

        try {
            $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
            $postData['filter'] = $this->prepareFilter($this->Request(), $profile->getType());

            $dataFactory = $this->Plugin()->getDataFactory();

            $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
            $dataSession = $dataFactory->loadSession($postData);
            $logger = $dataFactory->loadLogger($dataSession);

            $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

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

            $this->View()->assign(array('success' => true, 'position' => $position, 'count' => count($ids)));
        } catch (Exception $e) {
            $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
        }
    }

    public function exportAction()
    {
        if ($this->Request()->getParam('limit')) {
            $limit = $this->Request()->getParam('limit');
        }
        
        if ($this->Request()->getParam('offset')) {
            $offset = $this->Request()->getParam('offset');
        }

        $postData = array(
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format'),
            'sessionId' => $this->Request()->getParam('sessionId'),
            'fileName' => $this->Request()->getParam('fileName'),
            'filter' =>  array(),
            'limit' =>  array(
                'limit' => $limit,
                'offset' => $offset,
            ),
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        $postData['filter'] = $this->prepareFilter($this->Request(), $profile->getType());

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        // we create the file writer that will write (partially) the result file
        $fileFactory = $this->Plugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter($postData, $fileHelper);

        $fileLogWriter = $fileFactory->createFileWriter(array('format' => 'csv'), $fileHelper);
        $logger = $dataFactory->loadLogger($dataSession, $fileLogWriter);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];
        $username = Shopware()->Auth()->getIdentity()->username;
        
        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($username);

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);
            
        try {
            $post = $dataWorkflow->export($postData);

            $message = $post['position'] . ' ' . $profile->getType() . ' exported successfully';
            $logger->write($message, 'false');

            $logData = array(
                date("Y-m-d H:i:s"),
                $post['fileName'],
                $profile->getName(),
                $message,
                'true'
            );

            $logger->writeToFile($logData);

            return $this->View()->assign(array('s' => $profile, 'success' => true, 'data' => $post));
        } catch (Exception $e) {
            $logger->write($e->getMessage(), 'true');

            $logData = array(
                date("Y-m-d H:i:s"),
                $postData['fileName'],
                $profile->getName(),
                $e->getMessage(),
                'false'
            );

            $logger->writeToFile($logData);

            return $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
        }
    }

    public function prepareImportAction()
    {
        $postData = array(
            'sessionId' => $this->Request()->getParam('sessionId'),
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'import',
            'file' => $this->Request()->getParam('importFile')
        );

        if (empty($postData['file'])) {
            return $this->View()->assign(array('success' => false, 'msg' => 'Not valid file'));
        }

        //get file format
        $inputFileName = Shopware()->DocPath() . $postData['file'];
        $extension = pathinfo($inputFileName, PATHINFO_EXTENSION);

        if (!$this->isFormatValid($extension)) {
            return $this->View()->assign(array('success' => false, 'msg' => 'Not valid file format'));
        }

        $postData['format'] = $extension;

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData, null);

        if ($extension === 'xml') {
            $tree = json_decode($profile->getConfig("tree"), true);
            $fileReader->setTree($tree);
        }

        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);
        $logger = $dataFactory->loadLogger($dataSession);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $totalCount = $fileReader->getTotalCount($inputFileName);

        return $this->View()->assign(array('success' => true, 'position' => $position, 'count' => $totalCount));
    }

    public function importAction()
    {
        $postData = array(
            'type' => 'import',
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'importFile' => $this->Request()->getParam('importFile'),
            'sessionId' => $this->Request()->getParam('sessionId')
        );

        if($this->Request()->getParam('unprocessed')){
            $unprocessed = json_decode($this->Request()->getParam('unprocessed'), true);
        }

        $inputFile = Shopware()->DocPath() . $postData['importFile'];
        if (!isset($postData['format'])) {
            //get file format
            $postData['format'] = pathinfo($inputFile, PATHINFO_EXTENSION);
        }

        // we create the file reader that will read the result file
        /** @var \Shopware\Components\SwagImportExport\Factories\FileIOFactory $fileFactory */
        $fileFactory = $this->Plugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        $fileReader = $fileFactory->createFileReader($postData, $fileHelper);

        //load profile
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        //setting up the batch size
        $postData['batchSize'] = $profile->getType() === 'articlesImages' ? 1 : 50;

        /* @var $dataFactory Shopware\Components\SwagImportExport\Factories\DataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());

        $dataSession = $dataFactory->loadSession($postData);

        $fileLogWriter = $fileFactory->createFileWriter(array('format' => 'csv'), $fileHelper);
        /* @var $logger Shopware\Components\SwagImportExport\Logger\Logger */
        $logger = $dataFactory->loadLogger($dataSession, $fileLogWriter);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];
        $username = Shopware()->Auth()->getIdentity()->username;
        
        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($username);
        
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileReader->hasTreeStructure())
        );

        $sessionState = $dataIO->getSessionState();

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);

        try {
            $post = $dataWorkflow->import($postData, $inputFile);

            //unprocessed data
            if (isset($post['unprocessedData']) && $post['unprocessedData']) {

                $data = array(
                    'data' => $post['unprocessedData'],
                    'session' => array(
                        'prevState' => $sessionState,
                        'currentState' => $dataIO->getSessionState()
                    )
                );

                $pathInfo = pathinfo($inputFile);

                foreach ($data['data'] as $key => $value){
                    $outputFile = 'media/unknown/' . $pathInfo['filename'] . '-' . $key .'-tmp.csv';
                    $post['unprocessed'][] = array(
                        'profileName' => $key,
                        'fileName' => $outputFile
                    );
                    $this->afterImport($data, $key, $outputFile);
                }
            }

            if ($dataSession->getTotalCount() > 0 && ($dataSession->getTotalCount() == $post['position'])) {

                //unprocessed files
                if (isset($post['unprocessed']) || $unprocessed){
                    $unprocessedFileNames = $unprocessed ? $unprocessed : $post['unprocessed'];
                    $postProcessedData = $this->processData($unprocessedFileNames);
                }

                if ($postProcessedData) {
                    unset($post['unprocessedData']);
                    unset($post['sessionId']);
                    unset($post['adapter']);

                    //sends the unprocessed files
                    if (!empty($post['unprocessed'])){
                        $post['unprocessed'] = json_encode($post['unprocessed']);
                    }

                    $post = array_merge($post, $postProcessedData);
                }

                if ($logger->getMessage() === null) {
                    $message = $post['position'] . ' ' . $post['adapter'] . ' imported successfully';

                    $logger->write($message, 'false');

                    $logData = array(
                        date("Y-m-d H:i:s"),
                        $inputFile,
                        $profile->getName(),
                        $message,
                        'true'
                    );

                    $logger->writeToFile($logData);
                }
            }

            return $this->View()->assign(array('success' => true, 'data' => $post));
        } catch (\Exception $e) {
            $logger->write($e->getMessage(), 'true');

            $logData = array(
                date("Y-m-d H:i:s"),
                $inputFile,
                $profile->getName(),
                $e->getMessage(),
                'false'
            );

            $logger->writeToFile($logData);

            return $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
        }
    }

    /**
     * Checks for unprocessed data
     * Returns unprocessed file for import
     * 
     * @param array $unprocessedData
     * @return array
     */
    protected function processData(&$unprocessedData)
    {
        foreach ($unprocessedData as $index => $data){
            $inputFile = $data['fileName'];
            $file = Shopware()->DocPath() . $inputFile;

            if (file_exists($file)) {

                //renames
                $outputFileName = str_replace('-tmp', '-swag', $inputFile);
                $outputFile = Shopware()->DocPath() . $outputFileName;
                rename($inputFile, $outputFile);

                $profile = $this->Plugin()->getProfileFactory()->loadHiddenProfile($data['profileName']);
                $profileId = $profile->getId();

                $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader(array('format' => 'csv'), null);
                $totalCount = $fileReader->getTotalCount($outputFile);

                unset($unprocessedData[$index]);

                $postData = array(
                    'importFile' => $outputFileName,
                    'profileId' => $profileId,
                    'count' => $totalCount,
                    'position' => 0,
                    'format' => 'csv',
                    'load' => true,
                );

                if ($data['profileName'] === 'articlesImages'){
                    $postData['batchSize'] = 1;
                }

                return $postData;
            }
        }

        return false;
    }

    /**
     * Saves unprocessed data to csv file
     * 
     * @param array $data
     * @param string $profileName
     * @param string $outputFile
     */
    protected function afterImport($data, $profileName, $outputFile)
    {
        $fileFactory = $this->Plugin()->getFileIOFactory();

        //loads hidden profile for article
        $profile = $this->Plugin()->getProfileFactory()->loadHiddenProfile($profileName);

        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter(array('format' => 'csv'), $fileHelper);

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow(null, $profile, $dataTransformerChain, $fileWriter);
        $dataWorkflow->saveUnprocessedData($data, $profileName, Shopware()->DocPath() . $outputFile);
    }

    public function getSessionsAction()
    {
        $sessionRepository = $this->getSessionRepository();

        $query = $sessionRepository->getSessionsListQuery(
                $this->Request()->getParam('filter', array()),
                $this->Request()->getParam('sort', array()),
                $this->Request()->getParam('limit', 25),
                $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->getManager()->createPaginator($query);

        //returns the total count of the query
        $total = $paginator->count();

        //returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        foreach ($data as $key => $row) {
            $data[$key]['fileName'] = str_replace('media/unknown/', '', $row['fileName']);
            $data[$key]['fileSize'] = DataHelper::formatFileSize($row['fileSize']);
        }

        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $total
        ));
    }

    /**
     * Deletes a single order from the database.
     * Expects a single order id which placed in the parameter id
     */
    public function deleteSessionAction()
    {
        try {
            $data = $this->Request()->getParam('data');

            if (is_array($data) && isset($data['id'])) {
                $data = array($data);
            }

            foreach ($data as $record) {
                $sessionId = $record['id'];

                if (empty($sessionId) || !is_numeric($sessionId)) {
                    $this->View()->assign(array(
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'No valid Id')
                    );
                    return;
                }

                $entity = $this->getSessionRepository()->find($sessionId);
                $this->getManager()->remove($entity);
            }

            //Performs all of the collected actions.
            $this->getManager()->flush();

            $this->View()->assign(array(
                'success' => true,
                'data' => $this->Request()->getParams())
            );
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage())
            );
        }
    }

    /**
     * Returns the shopware model manager
     *
     * @return Shopware\Components\Model\ModelManager
     */
    protected function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }
        return $this->manager;
    }

    public function uploadFileAction()
    {
        $this->Front()->Plugins()->Json()->setRenderer(false);

        $albumRepo = $this->getManager()->getRepository('Shopware\Models\Media\Album');

        $album = $albumRepo->findOneBy(array('name' => 'ImportFiles'));

        if (!$album) {
            $album = new Shopware\Models\Media\Album();
            $album->setName('ImportFiles');
            $album->setPosition(0);
            $this->getManager()->persist($album);
            $this->getManager()->flush($album);
        }

        $id = $album->getId();

        $this->Request()->setParam('albumID', $id);

        $this->forward('upload', 'mediaManager');
    }

    /**
     * Fires when the user want to open a generated order document from the backend order module.
     * @return Returns the created pdf file with an echo.
     */
    public function downloadFileAction()
    {
        try {
            $fileName = $this->Request()->getParam('fileName', null);
            $fileType = $this->Request()->getParam('type', null);

            if ($fileName === null) {
                throw new \Exception('File name must be provided');
            }

            if ($fileType === null) {
//                throw new \Exception('Profile type must be provided');
            }

            //root directory
            $root = Shopware()->DocPath();

            if ($fileType === 'import') {
                $file = $root . 'media/unknown/' . $fileName;
            } else {
                $file = $root . 'files/import_export/' . $fileName;
            }

            //get file format
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            switch ($extension) {
                case 'csv':
                    $application = 'text/csv';
                    break;
                case 'xml':
                    $application = 'application/xml';
                    break;
                default:
                    throw new \Exception('File extension is not valid');
            }

            if (!file_exists($file)) {
                $this->View()->assign(array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => 'File not exist'
                ));
            }

            $response = $this->Response();
            $response->setHeader('Cache-Control', 'public');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename=' . $fileName);

            $response->setHeader('Content-Type', $application);
            readfile($file);
        } catch (\Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage()
            ));
            return;
        }

        Enlight_Application::Instance()->Events()->removeListener(new Enlight_Event_EventHandler('Enlight_Controller_Action_PostDispatch', ''));
    }

    public function getSectionsAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');

        if (!$postData['profileId']) {
            return $this->View()->assign(array(
                        'success' => false, 'message' => 'No profile Id'
            ));
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->Plugin()->getDataFactory()->createDbAdapter($type);
        
        $sections = $dbAdapter->getSections();
        
        $this->View()->assign(array(
            'success' => true, 
            'data' => $sections, 
            'total' => count($sections)
        ));
    }

    public function getColumnsAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');
        $section = $this->Request()->getParam('adapter', 'default');

        if (!$postData['profileId']) {
            return $this->View()->assign(array(
                        'success' => false, 'message' => 'No profile Id'
            ));
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->Plugin()->getDataFactory()->createDbAdapter($type);
        
        $columns = $dbAdapter->getColumns($section);

        if (!$columns || empty($columns)) {
            $this->View()->assign(array(
                'success' => false, 'msg' => 'No colums found.'
            ));
        }
        
        // merge all sections
        if ($section == 'default' && count($dbAdapter->getSections()) > 1) {
            $columns = array_reduce($columns, function($carry, $item) {
                return array_merge($carry, $item);
            }, array());
        }

        foreach ($columns as &$column) {
            $match = '';
            preg_match('/(?<=as ).*/', $column, $match);

            $match = trim($match[0]);

            if ($match != '') {
                $column = $match;
            } else {
                preg_match('/(?<=\.).*/', $column, $match);
                $match = trim($match[0]);
                if ($match != '') {
                    $column = $match;
                }
            }

            $column = array('id' => $column, 'name' => $column);
        }

        $this->View()->assign(array(
            'success' => true, 'data' => $columns, 'total' => count($columns)
        ));
    }

    public function getParentKeysAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');
        $section = $this->Request()->getParam('adapter', 'default');

        if (!$postData['profileId']) {
            return $this->View()->assign(array(
                        'success' => false, 'message' => 'No profile Id'
            ));
        }

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->Plugin()->getDataFactory()->createDbAdapter($type);

        if (!method_exists($dbAdapter, 'getParentKeys')) {
            $this->View()->assign(array(
                'success' => true, 'data' => array(), 'total' => 0
            ));
            return;
        }

        $columns = $dbAdapter->getParentKeys($section);

        foreach ($columns as &$column) {
            $match = '';
            preg_match('/(?<=as ).*/', $column, $match);

            $match = trim($match[0]);

            if ($match != '') {
                $column = $match;
            } else {
                preg_match('/(?<=\.).*/', $column, $match);
                $match = trim($match[0]);
                if ($match != '') {
                    $column = $match;
                }
            }

            $column = array('id' => $column, 'name' => $column);
        }

        $this->View()->assign(array(
            'success' => true, 'data' => $columns, 'total' => count($columns)
        ));
    }

    /**
     * Check is file format valid
     * 
     * @param string $extension
     * @return boolean
     */
    public function isFormatValid($extension)
    {
        switch ($extension) {
            case 'csv':
            case 'xml':
                return true;
            default:
                return false;
        }
    }

    /**
     * Helper Method to get access to the profile repository.
     *
     * @return Shopware\Models\Category\Repository
     */
    public function getProfileRepository()
    {
        if ($this->profileRepository === null) {
            $this->profileRepository = $this->getManager()->getRepository('Shopware\CustomModels\ImportExport\Profile');
        }
        return $this->profileRepository;
    }

    /**
     * Helper Method to get access to the category repository.
     *
     * @return Shopware\Models\Category\Repository
     */
    public function getSessionRepository()
    {
        if ($this->sessionRepository === null) {
            $this->sessionRepository = $this->getManager()->getRepository('Shopware\CustomModels\ImportExport\Session');
        }
        return $this->sessionRepository;
    }

    /**
     * Helper Method to get access to the conversion repository.
     *
     * @return Shopware\Models\Category\Repository
     */
    public function getExpressionRepository()
    {
        if ($this->expressionRepository === null) {
            $this->expressionRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Expression');
        }
        return $this->expressionRepository;
    }

    /**
     * Helper Method to get access to the logger repository.
     *
     * @return Shopware\Models\Category\Repository
     */
    public function getLoggerRepository()
    {
        if ($this->loggerRepository === null) {
            $this->loggerRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Logger');
        }
        return $this->loggerRepository;
    }

    /**
     * @return Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    public function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

    public function getLogsAction()
    {
        $loggerRepository = $this->getLoggerRepository();

        $query = $loggerRepository->getLogListQuery(
                $this->Request()->getParam('filter', array()), $this->Request()->getParam('sort', array()), $this->Request()->getParam('limit', 25), $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->getManager()->createPaginator($query);

        //returns the total count of the query
        $total = $paginator->count();

        //returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        $successStatus = SnippetsHelper::getNamespace()
            ->get('controller/log_status_success', 'No errors');


        foreach($data as &$log) {
            if ($log['state'] == 'false') {
                $log['state'] = $successStatus;
                $log['title'] = 'Success';
            } else {
                $log['title'] = 'Error';
            }
        }
        
        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $total
        ));
    }

    /**
     * Registers acl permissions for controller actions
     *
     * @return void
     */
    public function initAcl()
    {
        $this->addAclPermission("getProfiles", "profile", "Insuficient Permissions (getProfiles)");
        $this->addAclPermission("createProfiles", "profile", "Insuficient Permissions (createProfiles)");
        $this->addAclPermission("updateProfiles", "profile", "Insuficient Permissions (updateProfiles)");
        $this->addAclPermission("deleteProfiles", "profile", "Insuficient Permissions (deleteProfiles)");
        $this->addAclPermission("getProfile", "profile", "Insuficient Permissions (getProfile)");
        $this->addAclPermission("createNode", "export", "Insuficient Permissions (createNode)");
        $this->addAclPermission("updateNode", "export", "Insuficient Permissions (updateNode)");
        $this->addAclPermission("deleteNode", "export", "Insuficient Permissions (deleteNode)");
        $this->addAclPermission("duplicateProfile", "profile", "Insuficient Permissions (duplicateProfile)");
        $this->addAclPermission("getConversions", "export", "Insuficient Permissions (getConversions)");
        $this->addAclPermission("createConversion", "export", "Insuficient Permissions (createConversion)");
        $this->addAclPermission("updateConversion", "export", "Insuficient Permissions (updateConversion)");
        $this->addAclPermission("deleteConversion", "export", "Insuficient Permissions (deleteConversion)");
        $this->addAclPermission("prepareExport", "export", "Insuficient Permissions (prepareExport)");
        $this->addAclPermission("export", "export", "Insuficient Permissions (export)");
        $this->addAclPermission("prepareImport", "import", "Insuficient Permissions (prepareImport)");
        $this->addAclPermission("import", "import", "Insuficient Permissions (import)");
        $this->addAclPermission("getSessions", "read", "Insuficient Permissions (getSessions)");
        $this->addAclPermission("deleteSession", "export", "Insuficient Permissions (deleteSession)");
        $this->addAclPermission("uploadFile", "import", "Insuficient Permissions (uploadFile)");
        $this->addAclPermission("downloadFile", "export", "Insuficient Permissions (downloadFile)");
        $this->addAclPermission("getSections", "profile", "Insuficient Permissions (getSections)");
        $this->addAclPermission("getColumns", "profile", "Insuficient Permissions (getColumns)");
        $this->addAclPermission("getParentKeys", "profile", "Insuficient Permissions (getParentKeys)");
    }

    /**
     * Prepares filter array for export
     *
     * @param $request
     * @param $adapterType
     * @return array
     */
    protected function prepareFilter($request, $adapterType)
    {
        $data = array();

        if ($adapterType === 'articles'){
            $data['variants'] = $request->getParam('variants') ? true : false;
        }

        if ($request->getParam('categories') && $adapterType === 'articles') {
            $data['categories'] = array($request->getParam('categories'));
        }

        if ($request->getParam('stockFilter') && $adapterType === 'articlesInStock') {
            $data['stockFilter'] = $request->getParam('stockFilter');
            if($data['stockFilter'] == 'custom') {
                $data['direction'] = $request->getParam('customFilterDirection');
                $data['value'] = $request->getParam('customFilterValue');
            }
        }

        //order filter
        if ($request->getParam('ordernumberFrom') && $adapterType === 'orders') {
            $data['ordernumberFrom'] = $request->getParam('ordernumberFrom');
        }

        if ($request->getParam('dateFrom') && $adapterType === 'orders') {
            $dateFrom = $request->getParam('dateFrom');
            $data['dateFrom'] = new \DateTime($dateFrom);
        }

        if ($request->getParam('dateTo') && $adapterType === 'orders') {
            $dateTo = $request->getParam('dateTo');
            $dateTo = new Zend_Date($dateTo);
            $dateTo->setHour('23');
            $dateTo->setMinute('59');
            $dateTo->setSecond('59');
            $data['dateTo'] = $dateTo;
        }

        if ($request->getParam('orderstate') && $adapterType === 'orders') {
            $data['orderstate'] = $request->getParam('orderstate');
        }

        if ($request->getParam('paymentstate') && $adapterType === 'orders') {
            $data['paymentstate'] = $request->getParam('paymentstate');
        }

        return $data;
    }

    /**
     * This method is ajaxCalled and return the missing ShopwareFields(Database fields) of the selected Profile
     *
     * @return Enlight_View|Enlight_View_Default
     */
    public function validateProfileAction()
    {
        $profileId = (int)$this->request()->get('profileId');
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));

        $profileComparator = new ProfileComparator();
        $requiredFields = $profileComparator->compareProfile($profileEntity);

        if(count($requiredFields) > 0){
            return $this->View()->assign(array(
                'success' => false, 'missingFields' => $requiredFields
            ));
        }

        return $this->View()->assign(array(
            'success' => true
        ));
    }

}
