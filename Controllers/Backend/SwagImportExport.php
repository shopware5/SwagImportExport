<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\Logger\LogDataStruct;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Utils\TreeHelper;
use Shopware\Components\SwagImportExport\Utils\DataHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImageEditor
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExport extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
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
     * @var Shopware\CustomModels\ImportExport\Logger
     */
    protected $loggerRepository;

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'downloadFile'
        ];
    }

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
        $profileType = $profileEntity->getType();
        $defaultFields = array();

        $dataManager = $this->getPlugin()->getDataFactory()->createDataManager($profileType);
        if ($dataManager) {
            $defaultFields = $dataManager->getDefaultFields();
        }

        $tree = json_decode($profileEntity->getTree(), 1);

        if (isset($data['parentId'])) {
            $data = array($data);
        }

        $errors = false;
        
        foreach ($data as &$node) {
            if (!TreeHelper::changeNode($node, $tree, $defaultFields)) {
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
                } elseif (!TreeHelper::moveNode($changedNode, $tree)) {
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
            $profileModel = $this->getPlugin()->getProfileFactory()->createProfileModel($data);

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
    }

    /**
     * Returns the new profile
     */
    public function updateProfilesAction()
    {
        $data = $this->Request()->getParam('data', 1);
        
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $data['id']));

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
    }

    /**
     * Returns all profiles into an array
     */
    public function getProfilesAction()
    {
        /** @var \Shopware\CustomModels\ImportExport\Repository $profileRepository */
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

        /** @var \Shopware\CustomModels\ImportExport\Repository $expressionRepository */
        $expressionRepository = $this->getExpressionRepository();

        $filter = array_merge(array('p.id' => $profileId), $filter);

        $query = $expressionRepository->getExpressionsListQuery(
            $filter,
            $this->Request()->getParam('sort', array()),
            $this->Request()->getParam('limit', null),
            $this->Request()->getParam('start')
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
        $limit = null;
        if ($this->Request()->getParam('limit')) {
            $limit = $this->Request()->getParam('limit');
        }

        $offset = null;
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

        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);
        $postData['filter'] = $this->prepareFilter($this->Request(), $profile->getType());

        $dataFactory = $this->getPlugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $logger = $this->getLogger($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $maxRecordCount, $type, $format);

        $ids = $dataIO->preloadRecordIds()->getRecordIds();
        $idAmount = count($ids);

        if ($idAmount == 0) {
            $this->View()->assign(array('success' => false, 'msg' => 'No data to export', 'position' => 0, 'count' => 0));
            return;
        }

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $this->View()->assign(array('success' => true, 'position' => $position, 'count' => count($ids)));
    }

    public function exportAction()
    {
        $limit = null;
        if ($this->Request()->getParam('limit')) {
            $limit = $this->Request()->getParam('limit');
        }

        $offset = null;
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

        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);
        $postData['filter'] = $this->prepareFilter($this->Request(), $profile->getType());

        $dataFactory = $this->getPlugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        // we create the file writer that will write (partially) the result file
        $fileFactory = $this->getPlugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter($postData, $fileHelper);

        $logger = $this->getLogger($postData);

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

        $dataTransformerChain = $this->getPlugin()->getDataTransformerFactory()->createDataTransformerChain(
            $profile,
            array('isTree' => $fileWriter->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        try {
            $post = $dataWorkflow->export($postData);

            $message = $post['position'] . ' ' . $profile->getType() . ' exported successfully';
            $logger->write($message, 'false');

            $logDataStruct = new LogDataStruct(
                date("Y-m-d H:i:s"),
                $post['fileName'],
                $profile->getName(),
                $message,
                'true'
            );

            $logger->writeToFile($logDataStruct);

            unset($post['filter']);
            $this->View()->assign(array('s' => $profile, 'success' => true, 'data' => $post));
        } catch (Exception $e) {
            $logger->write($e->getMessage(), 'true');

            $logDataStruct = new LogDataStruct(
                date("Y-m-d H:i:s"),
                $postData['fileName'],
                $profile->getName(),
                $e->getMessage(),
                'false'
            );

            $logger->writeToFile($logDataStruct);

            throw $e;
        }
    }

    public function prepareImportAction()
    {
        $request = $this->Request();
        $shopUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/';
        $postData = array(
            'sessionId' => $request->getParam('sessionId'),
            'profileId' => (int) $request->getParam('profileId'),
            'type' => 'import',
            'file' => $request->getParam('importFile')
        );

        if (empty($postData['file'])) {
            $this->View()->assign(array('success' => false, 'msg' => 'Not valid file'));
            return;
        }

        // get file format
        $inputFileName = str_replace($shopUrl, '', $postData['file']);
        $extension = pathinfo($inputFileName, PATHINFO_EXTENSION);

        if (!$this->isFormatValid($extension)) {
            $this->View()->assign(array('success' => false, 'msg' => 'Not valid file format'));
            return;
        }

        $postData['format'] = $extension;

        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        // we create the file reader that will read the result file
        $fileReader = $this->getPlugin()->getFileIOFactory()->createFileReader($postData, null);

        if ($extension === 'xml') {
            $tree = json_decode($profile->getConfig("tree"), true);
            $fileReader->setTree($tree);
        }

        $dataFactory = $this->getPlugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $logger = $this->getLogger($postData);

        // create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $totalCount = $fileReader->getTotalCount($inputFileName);

        $this->View()->assign(array('success' => true, 'position' => $position, 'count' => $totalCount));
    }

    public function importAction()
    {
        $request = $this->Request();
        $shopUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . '/';

        $inputFile = str_replace($shopUrl, '', $request->getParam('importFile'));

        $unprocessedFiles = [];
        $postData = [
            'type' => 'import',
            'profileId' => (int) $request->getParam('profileId'),
            'importFile' => $inputFile,
            'sessionId' => $request->getParam('sessionId'),
            'limit' => []
        ];

        if ($request->getParam('unprocessedFiles')) {
            $unprocessedFiles = json_decode($request->getParam('unprocessedFiles'), true);
        }

        if (!isset($postData['format'])) {
            // get file format
            $postData['format'] = pathinfo($inputFile, PATHINFO_EXTENSION);
        }

        // we create the file reader that will read the result file
        /** @var \Shopware\Components\SwagImportExport\Factories\FileIOFactory $fileFactory */
        $fileFactory = $this->getPlugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        $fileReader = $fileFactory->createFileReader($postData, $fileHelper);

        // load profile
        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);

        // get profile type
        $postData['adapter'] = $profile->getType();

        // setting up the batch size
        $postData['batchSize'] = $profile->getType() === 'articlesImages' ? 1 : 50;

        /* @var $dataFactory Shopware\Components\SwagImportExport\Factories\DataFactory */
        $dataFactory = $this->getPlugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());

        $dataSession = $dataFactory->loadSession($postData);

        $logger = $this->getLogger($postData);

        // create dataIO
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
        
        $dataTransformerChain = $this->getPlugin()->getDataTransformerFactory()->createDataTransformerChain(
            $profile,
            array('isTree' => $fileReader->hasTreeStructure())
        );

        $sessionState = $dataIO->getSessionState();

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);

        try {
            $post = $dataWorkflow->import($postData, $inputFile);

            // unprocessed data
            if (isset($post['unprocessedData']) && $post['unprocessedData']) {
                $data = array(
                    'data' => $post['unprocessedData'],
                    'session' => array(
                        'prevState' => $sessionState,
                        'currentState' => $dataIO->getSessionState()
                    )
                );

                $pathInfo = pathinfo($inputFile);

                foreach ($data['data'] as $key => $value) {
                    $outputFile = 'media/unknown/' . $pathInfo['filename'] . '-' . $key .'-tmp.csv';
                    $this->afterImport($data, $key, $outputFile);
                    $unprocessedFiles[$key] = $outputFile;
                }
            }

            if ($dataSession->getTotalCount() > 0 && ($dataSession->getTotalCount() == $post['position'])) {
                // unprocessed files
                $postProcessedData = null;
                if ($unprocessedFiles) {
                    $postProcessedData = $this->processData($unprocessedFiles);
                }

                if ($postProcessedData) {
                    unset($post['sessionId']);
                    unset($post['adapter']);

                    $post = array_merge($post, $postProcessedData);
                }

                if ($logger->getMessage() === null) {
                    $message = $post['position'] . ' ' . $post['adapter'] . ' imported successfully';

                    $logger->write($message, 'false');

                    $logDataStruct = new LogDataStruct(
                        date("Y-m-d H:i:s"),
                        $inputFile,
                        $profile->getName(),
                        $message,
                        'true'
                    );

                    $logger->writeToFile($logDataStruct);
                }
            }

            unset($post['unprocessedData']);
            $post['unprocessedFiles'] = json_encode($unprocessedFiles);

            $this->View()->assign(array('success' => true, 'data' => $post));
        } catch (\Exception $e) {
            $logger->write($e->getMessage(), 'true');

            $logDataStruct = new LogDataStruct(
                date("Y-m-d H:i:s"),
                $inputFile,
                $profile->getName(),
                $e->getMessage(),
                'false'
            );

            $logger->writeToFile($logDataStruct);

            $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
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
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');

        foreach ($unprocessedData as $hiddenProfile => $inputFile) {
            if ($mediaService->has($inputFile)) {
                // renames
                $outputFile = str_replace('-tmp', '-swag', $inputFile);
                $mediaService->rename($inputFile, $outputFile);

                $profile = $this->getPlugin()->getProfileFactory()->loadHiddenProfile($hiddenProfile);
                $profileId = $profile->getId();

                $fileReader = $this->getPlugin()->getFileIOFactory()->createFileReader(array('format' => 'csv'), null);
                $totalCount = $fileReader->getTotalCount($mediaService->getUrl($outputFile));

                unset($unprocessedData[$hiddenProfile]);

                $postData = array(
                    'importFile' => $mediaService->getUrl($outputFile),
                    'profileId' => $profileId,
                    'count' => $totalCount,
                    'position' => 0,
                    'format' => 'csv',
                    'load' => true,
                );

                if ($hiddenProfile === 'articlesImages') {
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
        $fileFactory = $this->getPlugin()->getFileIOFactory();

        //loads hidden profile for article
        $profile = $this->getPlugin()->getProfileFactory()->loadHiddenProfile($profileName);

        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter(array('format' => 'csv'), $fileHelper);

        $dataTransformerChain = $this->getPlugin()->getDataTransformerFactory()
            ->createDataTransformerChain($profile, array('isTree' => $fileWriter->hasTreeStructure()));

        $dataWorkflow = new DataWorkflow(null, $profile, $dataTransformerChain, $fileWriter);
        $dataWorkflow->saveUnprocessedData($data, $profileName, $outputFile);
    }

    public function getSessionsAction()
    {
        /** @var \Shopware\CustomModels\ImportExport\Repository $sessionRepository */
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
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');

        foreach ($data as $key => $row) {
            $data[$key]['fileUrl'] = $row['fileName'];
            $data[$key]['fileName'] = str_replace('media/unknown/', '', $mediaService->normalize($row['fileName']));
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
                        'message' => 'No valid Id'
                    ));
                    return;
                }

                $entity = $this->getSessionRepository()->find($sessionId);
                $this->getManager()->remove($entity);
            }

            //Performs all of the collected actions.
            $this->getManager()->flush();

            $this->View()->assign(array(
                'success' => true,
                'data' => $this->Request()->getParams()
            ));
        } catch (Exception $e) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage()
            ));
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
     *
     * Returns the created pdf file with an echo.
     */
    public function downloadFileAction()
    {
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');

        try {
            $fileName = $this->Request()->getParam('fileName', null);
            $fileType = $this->Request()->getParam('type', null);

            if ($fileName === null) {
                throw new \Exception('File name must be provided');
            }

            if ($fileType === 'import') {
                $file = 'media/unknown/' . $fileName;
            } else {
                $file = 'files/import_export/' . $fileName;
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

            if (!$mediaService->has($file)) {
                $this->View()->assign(
                    array(
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'File not exist'
                    )
                );
            }

            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Front()->Plugins()->Json()->setRenderer(false);

            $response = $this->Response();
            $response->setHeader('Cache-Control', 'public');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename=' . $fileName);

            $response->setHeader('Content-Type', $application);
            print $mediaService->read($file);
        } catch (\Exception $e) {
            $this->View()->assign(
                array(
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $e->getMessage()
                )
            );

            return;
        }
    }

    public function getSectionsAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');

        if (!$postData['profileId']) {
            $this->View()->assign(array(
                'success' => false, 'message' => 'No profile Id'
            ));
            return;
        }

        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->getPlugin()->getDataFactory()->createDbAdapter($type);
        
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
            $this->View()->assign(array(
                'success' => false, 'message' => 'No profile Id'
            ));
            return;
        }

        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->getPlugin()->getDataFactory()->createDbAdapter($type);
        $dataManager = $this->getPlugin()->getDataFactory()->createDataManager($type);

        $defaultFieldsName = null;
        $defaultFields = array();
        if ($dataManager) {
            $defaultFields = $dataManager->getDefaultFields();
            $defaultFieldsName = $dataManager->getDefaultFieldsName();
        }

        $columns = $dbAdapter->getColumns($section);

        if (!$columns || empty($columns)) {
            $this->View()->assign(array(
                'success' => false, 'msg' => 'No colums found.'
            ));
        }
        
        // merge all sections
        if ($section == 'default' && count($dbAdapter->getSections()) > 1) {
            $columns = array_reduce($columns, function ($carry, $item) {
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

            if ($defaultFieldsName) {
                if (in_array($column['name'], $defaultFieldsName)) {
                    $column['default'] = true;
                    $column['type'] = $dataManager->getFieldType($column['name'], $defaultFields);
                }
            }
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
            $this->View()->assign(array(
                'success' => false, 'message' => 'No profile Id'
            ));
            return;
        }

        $profile = $this->getPlugin()->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->getPlugin()->getDataFactory()->createDbAdapter($type);

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
    public function getPlugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

    public function getLogsAction()
    {
        /** @var \Shopware\CustomModels\ImportExport\Repository $loggerRepository */
        $loggerRepository = $this->getLoggerRepository();

        $query = $loggerRepository->getLogListQuery(
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

        $successStatus = SnippetsHelper::getNamespace()
            ->get('controller/log_status_success', 'No errors');


        foreach ($data as &$log) {
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
     * @param Enlight_Controller_Request_Request $request
     * @param $adapterType
     * @return array
     */
    protected function prepareFilter($request, $adapterType)
    {
        $data = array();

        //articles filter
        if ($adapterType === 'articles') {
            $data['variants'] = $request->getParam('variants') ? true : false;
            if ($request->getParam('categories')) {
                $data['categories'] = array($request->getParam('categories'));
            }
        }

        //articlesInStock filter
        if ($request->getParam('stockFilter') && $adapterType === 'articlesInStock') {
            $data['stockFilter'] = $request->getParam('stockFilter');
            if ($data['stockFilter'] == 'custom') {
                $data['direction'] = $request->getParam('customFilterDirection');
                $data['value'] = $request->getParam('customFilterValue');
            }
        }

        //orders and mainOrders filter
        if (in_array($adapterType, array('orders', 'mainOrders'), true)) {
            if ($request->getParam('ordernumberFrom')) {
                $data['ordernumberFrom'] = $request->getParam('ordernumberFrom');
            }

            if ($request->getParam('dateFrom')) {
                $dateFrom = $request->getParam('dateFrom');
                $data['dateFrom'] = new \DateTime($dateFrom);
            }

            if ($request->getParam('dateTo')) {
                $dateTo = $request->getParam('dateTo');
                $dateTo = new Zend_Date($dateTo);
                $dateTo->setHour('23');
                $dateTo->setMinute('59');
                $dateTo->setSecond('59');
                $data['dateTo'] = $dateTo;
            }

            $orderState = $request->getParam('orderstate');
            if (isset($orderState)) {
                $data['orderstate'] = $orderState;
            }

            $paymentState = $request->getParam('paymentstate');
            if (isset($paymentState)) {
                $data['paymentstate'] = $paymentState;
            }
        }

        return $data;
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return Shopware()->Container()->get('swag_import_export.logger');
    }
}
