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
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $manager;
    /**
     * @var Shopware\CustomModels\ImportExport\Profile
     */
    protected $profileRepository;    
    /*
     * @var Shopware\CustomModels\ImportExport\Session
     */
    protected $sessionRepository;
    
    
	protected $nextNodeId = 0;
    
    protected function convertToExtJSTree($node, $isInIteration = false)
    {
        $extjsNode = array("id" => $node['id']);
        $onlyAttributes = false;

        if ($node['type'] == 'record') {
            $isIteration = true;

            $extjsNode['iconCls'] = 'sprite-blue-folders-stack';
            $extjsNode['type'] = 'iteration';
        } else {
            $isIteration = false;
        }

        if (isset($node['name'])) {
            $extjsNode['text'] = $node['name'];
        }
        if (isset($node['children'])) {
            $extjsNode['expanded'] = true;
            foreach ($node['children'] as $child) {
                $extjsNode['children'][] = $this->convertToExtJSTree($child, $isIteration | $isInIteration);
            }
        }
        if (isset($node['attributes'])) {
            if (!isset($extjsNode['children'])) {
                $onlyAttributes = true;
                $extjsNode['expanded'] = true;
                $extjsNode['children'] = array();
            }
            foreach ($node['attributes'] as $attribute) {
                $extjsNode['children'][] = array("id" => $attribute['id'], 'text' => $attribute['name'], 'leaf' => true, 'iconCls' => 'sprite-sticky-notes-pin', 'type' => 'attribute', 'swColumn' => $attribute['shopwareField']);
            }
        }
        if (!isset($extjsNode['children']) || $onlyAttributes) {
            if ($isInIteration) {
                $extjsNode['iconCls'] = 'sprite-icon_taskbar_top_inhalte_active';
                if (!$onlyAttributes) {
                    $extjsNode['leaf'] = true;
                }
                $extjsNode['type'] = 'node';
                $extjsNode['swColumn'] = $node['shopwareField'];
            } else {
                $extjsNode['expanded'] = true;
                $extjsNode['children'] = array();
            }
        }
        
        $extjsNode['inIteration'] = $isIteration | $isInIteration;

        return $extjsNode;
    }

    protected function getTree()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $postData = array(
            'profileId' => $profileId,
            'sessionId' => 70,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);
        
        return $profile->getConfig('tree');
    }
    
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
        $tree = $this->getTree();
        $root = $this->convertToExtJSTree(json_decode($tree, 1));
//        
//        echo '<pre>';
//        print_r(json_decode($tree, 1));
//        echo '</pre>';

        $this->View()->assign(array('success' => true, 'children' => $root['children']));
    }
    
    public function createProfileAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $postData = array(
            'profileId' => $profileId,
            'sessionId' => 70,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $tree = json_decode($profile->getConfig('tree'), 1);
        
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
                
        $profile->setConfig('tree', json_encode($tree));
        $profile->persist();
        
        Shopware()->Models()->flush();
        
        if ($errors) {
            $this->View()->assign(array('success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data));
        } else {
            $this->View()->assign(array('success' => true, 'children' => $data));
        }
    }
    
    public function updateProfileAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $postData = array(
            'profileId' => $profileId,
            'sessionId' => 70,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $tree = json_decode($profile->getConfig('tree'), 1);
        
        if (isset($data['parentId'])) {
            $data = array($data);
        }
        
        $errors = false;

        foreach ($data as &$node) {
            if (!$this->changeNode($node, $tree)) {
                $errors = true;
            }
        }
                
        $profile->setConfig('tree', json_encode($tree));
        $profile->persist();
        
        Shopware()->Models()->flush();
        
        if ($errors) {
            $this->View()->assign(array('success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data));
        } else {
            $this->View()->assign(array('success' => true, 'children' => $data));
        }
    }
    
    public function deleteProfileAction()
    {
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $postData = array(
            'profileId' => $profileId,
            'sessionId' => 70,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $tree = json_decode($profile->getConfig('tree'), 1);
        
        if (isset($data['parentId'])) {
            $data = array($data);
        }
        
        $errors = false;

        foreach ($data as &$node) {
            if (!$this->deleteNode($node, $tree)) {
                $errors = true;
            }
        }
                
        $profile->setConfig('tree', json_encode($tree));
        $profile->persist();
        
        Shopware()->Models()->flush();
        
        if ($errors) {
            $this->View()->assign(array('success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data));
        } else {
            $this->View()->assign(array('success' => true, 'children' => $data));
        }
    }

    /**
     * Returns all profiles into an array
     */
    public function createProfilesAction()
    {
        $profileRepository = $this->getProfileRepository();
        $data = $this->Request()->getParam('data', 1);
        $newTree = '{ "name": "Root", "children": [{ "name": "Header", "children": [], "id": "537359399c80a" }, { "name": "Categories", "children": [{ "name": "Category", "type": "record", "attributes": [], "children": [], "id": "537359399c90d" }], "id": "537359399c8b7" }], "id": "root" }';
        
        $profile = new \Shopware\CustomModels\ImportExport\Profile();
        
        $profile->setName($data['name']);
        $profile->setType($data['type']);
        $profile->setTree($newTree);
        
        Shopware()->Models()->persist($profile);
        Shopware()->Models()->flush();
        
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
        
        $count = Shopware()->Models()->getQueryCount($query);

        $data = $query->getArrayResult();
        
        $this->View()->assign(array(
            'success' => true, 'data' => $data, 'total' => $count
        ));
    }

    public function prepareExportAction()
    {
        $postData = array(
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format')
        );

        //todo: check for session

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

        if ($dataIO->getSessionState() == 'new') {
            //todo: create file here ?
            $fileName = $dataIO->generateFileName($profile);

            $outputFileName = Shopware()->DocPath() . 'files/import_export/' . $fileName;

            // session has no ids stored yet, therefore we must start it and write the file headers
            $header = $dataTransformerChain->composeHeader();
            $fileWriter->writeHeader($outputFileName, $header);

            $dataIO->startSession();
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

        return $this->View()->assign(array('success' => true, 'data' => $post));
    }

    public function prepareImportAction()
    {
        $postData = array(
            'type' => 'import',
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'file' => $this->Request()->getParam('importFile')
        );

        if (empty($postData['file'])) {
            return $this->View()->assign(array('success' => false, 'msg' => 'Not valid file'));
        }
        
        //get file format
        $inputFileName = Shopware()->DocPath() . $postData['file'];
        $extension = pathinfo($inputFileName, PATHINFO_EXTENSION);

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
        
        $totalCount = $fileReader->getTotalCount($inputFileName);

        return $this->View()->assign(array('success' => true, 'position' => 0, 'count' => $totalCount));
    }

    public function importAction()
    {
        $postData = array(
            'type' => 'import',
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'importFile' => $this->Request()->getParam('importFile'),
            'sessionId' => $this->Request()->getParam('sessionId')
        );

        //get file format
        $inputFileName = Shopware()->DocPath() . $postData['importFile'];
        $extension = pathinfo($inputFileName, PATHINFO_EXTENSION);

        $postData['format'] = $extension;
        
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
        
        if($extension === 'xml'){
            $tree = json_decode($profile->getConfig("tree"), true);
            $fileReader->setTree($tree);            
        }
                
        if ($dataIO->getSessionState() == 'new') {

            $totalCount = $fileReader->getTotalCount($inputFileName);
            
            $dataIO->getDataSession()->setFileName($inputFileName);

            $dataIO->getDataSession()->setTotalCount($totalCount);

            $dataIO->startSession();
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $dataIO->resumeSession();
        }
        
        if ($dataIO->getSessionState() == 'active') {

            try {

                //get current session position
                $position = $dataIO->getSessionPosition();

                $records = $fileReader->readRecords($inputFileName, $position, 100);

                $data = $dataTransformerChain->transformBackward($records);
                
                $dataIO->write($data);
                
                $dataIO->progressSession(100);
            } catch (Exception $e) {
                // we need to analyze the exception somehow and decide whether to break the while loop;
                // there is a danger of endless looping in case of some read error or transformation error;
                // may be we use
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
            $this->Request()->getParam('limit', null),
            $this->Request()->getParam('start')
                )->getQuery();

        $data = $query->getArrayResult();

        $this->View()->assign(array(
            'success' => true, 'data' => $data
        ));
    }
    
    /**
     * Deletes a single order from the database.
     * Expects a single order id which placed in the parameter id
     */
    public function deleteSessionAction()
    {
        try {
            $sessionId = $this->Request()->getParam('id');

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
                case 'csv':
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
            $response->setHeader('Content-Transfer-Encoding', 'binary');
            $response->setHeader('Content-Length', filesize($file));
            echo readfile($file);
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

    /**
     * Helper Method to get access to the category repository.
     *
     * @return Shopware\Models\Category\Repository
     */
    public function getProfileRepository()
    {
        if ($this->profileRepository === null) {
            $this->profileRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Profile');
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
            $this->sessionRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Session');
        }
        return $this->sessionRepository;
    }

    public function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

}
