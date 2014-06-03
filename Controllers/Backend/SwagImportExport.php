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
     * Converts the JSON tree to ExtJS tree
     *  
     * @TODO: move code to component
     */
    protected function convertToExtJSTree($node, $isInIteration = false)
    {
        $isIterationNode = false;
        $type = '';
        $isLeaf = true;
        $children = array();

        // Check if the current node is in the iteration
        if ($isInIteration) {
            $type = 'node';
            $icon = 'sprite-icon_taskbar_top_inhalte_active';
        } else if ($node['type'] == 'record') {
            $isIterationNode = true;
            $type = 'iteration';
            $icon = 'sprite-blue-folders-stack';
        } else {
            $isLeaf = false;
        }

        // Get the attributes
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $attribute) {
                $children[] = array(
                    'id' => $attribute['id'],
                    'text' => $attribute['name'],
                    'leaf' => true,
                    'iconCls' => 'sprite-sticky-notes-pin',
                    'type' => 'attribute',
                    'swColumn' => $attribute['shopwareField'],
                    'inIteration' => $isInIteration | $isIterationNode
                );
            }
        }

        // Get the child nodes
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                $children[] = $this->convertToExtJSTree($child, $isInIteration | $isIterationNode);
            }
            
            if ($isInIteration) {
                $type = '';
                $icon = '';
            }

            $isLeaf = false;
        }
            
        return array(
            'id' => $node['id'],
            'text' => $node['name'],
            'type' => $type,
            'leaf' => $isLeaf,
            'expanded' => !$isLeaf,
            'iconCls' => $icon,
            'swColumn' => isset($node['shopwareField']) ? $node['shopwareField'] : $node['shopwareField'],
            'inIteration' => $isInIteration | $isIterationNode,
            'children' => $children
        );
    }
    
    /**
     * Helper function which appends child node to the tree
     */
    protected function appendNode($child, &$node)
    {
        if ($node['id'] == $child['parentId']) {
            if ($child['type'] == 'attribute') {
                $node['attributes'][] = array(
                    'id' => $child['id'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                );
            } else if ($child['type'] == 'node') {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                );
            } else {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'name' => $child['text'],
                );
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if ($this->appendNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
    
    /**
     * Helper function which finds and changes node from the tree
     */
    protected function changeNode($child, &$node)
    {
        if ($node['id'] == $child['id']) {
            $node['name'] = $child['text'];
            if (isset($child['swColumn'])) {
                $node['shopwareField'] = $child['swColumn'];
            } else {
                unset($node['shopwareField']);
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if ($this->changeNode($child, $childNode)) {
                        return true;
                    }
                }
            }
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as &$childNode) {
                    if ($this->changeNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and deletes node from the tree
     */
    protected function deleteNode($child, &$node)
    {
        if (isset($node['children'])) {
            foreach ($node['children'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['children'][$key]);
                    return true;
                } else if ($this->deleteNode($child, $childNode)) {
                    return true;
                }
            }
        }
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['attributes'][$key]);
                    return true;
                } else if ($this->deleteNode($child, $childNode)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getProfileAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $profileRepository = $this->getProfileRepository();
        $profileEntity = $profileRepository->findOneBy(array('id' => $profileId));
        
        $tree = $profileEntity->getTree();
        $root = $this->convertToExtJSTree(json_decode($tree, 1));

        $this->View()->assign(array('success' => true, 'children' => $root['children']));
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
            if (!$this->appendNode($node, $tree)) {
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
            if (!$this->changeNode($node, $tree)) {
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
            if (!$this->deleteNode($node, $tree)) {
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
        $newTree = '{"name":"Root","children":[{"name":"Header","children":[{"id":"537385ed7c799","name":"HeaderChild","shopwareField":""}],"id":"537359399c80a"},{"name":"Categories","children":[{"name":"Category","type":"record","attributes":[{"id":"53738653da10f","name":"Attribute1","shopwareField":"parent"}],"children":[{"id":"5373865547d06","name":"Id","shopwareField":"id"},{"id":"537386ac3302b","name":"Description","shopwareField":"description","children":[{"id":"5373870d38c80","name":"Value","shopwareField":"description"}],"attributes":[{"id":"53738718f26db","name":"Attribute2","shopwareField":"active"}]},{"id":"537388742e20e","name":"Title","shopwareField":"description"}],"id":"537359399c90d"}],"id":"537359399c8b7"}],"id":"root"}';
        
        $profile = new \Shopware\CustomModels\ImportExport\Profile();
        
        $profile->setName($data['name']);
        $profile->setType($data['type']);
        $profile->setTree($newTree);
        
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
     * Returns all profiles into an array
     */
    public function getProfilesAction()
    {
        $profileRepository = $this->getProfileRepository();
        
        $query = $profileRepository->getProfilesListQuery(
            $this->Request()->getParam('filter', array()),
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
            $this->View()->assign(array('success' => false, 'message' => 'Unexpected error. The profile could not be deleted.', 'children' => $data));
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
        $postData = array(
            'sessionId' => $this->Request()->getParam('sessionId'),
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format')
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataIO = $dataFactory->createDataIO($postData);

        $ids = $dataIO->preloadRecordIds()->getRecordIds();

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $this->View()->assign(array('success' => true, 'position' => $position, 'count' => count($ids)));
    }

    public function exportAction()
    {
        $postData = array(
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format'),
            'sessionId' => $this->Request()->getParam('sessionId'),
            'fileName' => $this->Request()->getParam('fileName')
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        //create dataIO
        $dataFactory = $this->Plugin()->getDataFactory();
        $dataIO = $dataFactory->createDataIO($postData);

        // we create the file writer that will write (partially) the result file
        $fileWriter = $this->Plugin()->getFileIOFactory()->createFileWriter($postData);

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );
        
        if ($dataIO->getSessionState() == 'closed') {
            $postData['position'] = $dataIO->getSessionPosition();
            $postData['fileName'] = $dataIO->getDataSession()->getFileName();
            
            return $this->View()->assign(array('success' => true, 'data' => $postData));
        }

        if ($dataIO->getSessionState() == 'new') {
            //todo: create file here ?
            $fileName = $dataIO->generateFileName($profile);
            $directory = $dataIO->getDirectory();
            
            $outputFileName = $directory . $fileName;

            // session has no ids stored yet, therefore we must start it and write the file headers
            $header = $dataTransformerChain->composeHeader();
            $fileWriter->writeHeader($outputFileName, $header);

            $dataIO->startSession($profile->getEntity());
        } else {
            $fileName = $dataIO->getDataSession()->getFileName();

            $outputFileName = Shopware()->DocPath() . 'files/import_export/' . $fileName;

            // session has already loaded ids and some position, so we simply activate it
            $dataIO->resumeSession();
        }
        $dataIO->preloadRecordIds();

        if ($dataIO->getSessionState() == 'active') {

            try {
                // read a bunch of records into simple php array;
                // the count of records may be less than 100 if we are at the end of the read.
                $data = $dataIO->read(1000);

                // process that array with the full transformation chain
                $data = $dataTransformerChain->transformForward($data);

                // now the array should be a tree and we write it to the file
                $fileWriter->writeRecords($outputFileName, $data);

                // writing is successful, so we write the new position in the session;
                // if if the new position goes above the limits provided by the 
                $dataIO->progressSession(1000);
            } catch (Exception $e) {
                return $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
            }
        }

        $position = $dataIO->getSessionPosition();

        $post = $postData;
        $post['position'] = $position == null ? 0 : $position;

        if (!$post['sessionId']) {
            $post['sessionId'] = $dataIO->getDataSession()->getId();
        }
        
        if ($dataIO->getSessionState() == 'finished') {
            // Session finished means we have exported all the ids in the sesssion.
            // Therefore we can close the file with a footer and mark the session as done.
            $footer = $dataTransformerChain->composeFooter();
            $fileWriter->writeFooter($outputFileName, $footer);
            $dataIO->closeSession();
        }
        if (!$post['fileName']) {
            $post['fileName'] = $fileName;
        }

        return $this->View()->assign(array('success' => true, 'data' => $post));
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
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData);

        if($extension === 'xml'){
            $tree = json_decode($profile->getConfig("tree"), true);
            $fileReader->setTree($tree);            
        }
        
        //create dataIO
        $dataIO = $this->Plugin()->getDataFactory()->createDataIO($postData);
        
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
        
        $inputFile = Shopware()->DocPath() . $postData['importFile'];
        if (!isset($postData['format'])){
            //get file format
            $postData['format'] = pathinfo($inputFile, PATHINFO_EXTENSION);            
        }

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData);
        
        //load profile
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        
        //get profile type
        $postData['adapter'] = $profile->getType();
        
        //create dataIO
        $dataIO = $this->Plugin()->getDataFactory()->createDataIO($postData);
        
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
            $profile, array('isTree' => $fileReader->hasTreeStructure())
        );
        
        if($postData['format'] === 'xml'){
            $tree = json_decode($profile->getConfig("tree"), true);
            $fileReader->setTree($tree);            
        }
        if ($dataIO->getSessionState() == 'new') {
            
            $totalCount = $fileReader->getTotalCount($inputFile);
            
            $dataIO->getDataSession()->setFileName($postData['importFile']);

            $dataIO->getDataSession()->setTotalCount($totalCount);

            $dataIO->startSession($profile->getEntity());
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $dataIO->resumeSession();
        }
        
        if ($dataIO->getSessionState() == 'active') {

            try {

                //get current session position
                $position = $dataIO->getSessionPosition();
                
                $records = $fileReader->readRecords($inputFile, $position, 100);

                $data = $dataTransformerChain->transformBackward($records);
                
                $dataIO->write($data);
                
                $dataIO->progressSession(100);
            } catch (Exception $e) {
                return $this->View()->assign(array('success' => false, 'msg' => $e->getMessage()));
            }
        }
        $position = $dataIO->getSessionPosition();
        $post = $postData;
        $post['position'] = $position == null ? 0 : $position;

        if (!$post['sessionId']) {
            $post['sessionId'] = $dataIO->getDataSession()->getId();
        }
        
        if ($dataIO->getSessionState() == 'finished') {
            $dataIO->closeSession();
        }
                
        return $this->View()->assign(array('success' => true, 'data' => $post));
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
            $sessionId = (int) $this->Request()->getParam('id');
            
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
            $name = $this->Request()->getParam('fileName', null);
            
            
            $file = Shopware()->DocPath() . 'files/import_export/' . $name;
            
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
            $response->setHeader('Content-disposition', 'attachment; filename=' . $name);
            
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

    public function getColumnsAction()
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
        $columns = $dbAdapter->getDefaultColumns();
        
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

    public function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }
    
}