<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Utils;

use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\FileIO\FileReader;
use Shopware\Components\SwagImportExport\FileIO\FileWriter;
use Shopware\Components\SwagImportExport\Logger\LogDataStruct;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\Transformers\DataTransformerChain;
use Shopware\Components\SwagImportExport\StatusLogger;

class CommandHelper
{
    // required
    /**
     * @var \Shopware\CustomModels\ImportExport\Profile
     */
    protected $profileEntity;
    protected $filePath;
    protected $format;

    // optional
    protected $exportVariants;
    protected $limit;
    protected $offset;
    protected $username;
    protected $category;

    //private
    protected $sessionId;

    /**
     * Tries to find profile by given name
     *
     * @param string $filename
     * @param \Shopware\CustomModels\ImportExport\Repository $repository
     * @return bool|Profile
     */
    public static function findProfileByName($filename, $repository)
    {
        $parts = explode('.', $filename);
        foreach ($parts as $part) {
            $part = strtolower($part);
            $profileEntity = $repository->findOneBy(array('name' => $part));
            if ($profileEntity !== null) {
                return $profileEntity;
            }
        }

        return false;
    }

    /**
     * Construct
     *
     * @param array $data
     * @throws \Exception
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
        if (isset($data['category'])) {
            $this->category = $data['category'];
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
        if ($this->category) {
            $postData['filter']['categories'] = $this->category;
        }

        /** @var Profile $profile */
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());

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
        $logger = $this->getLogger();

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
        if ($this->category) {
            $postData['filter']['categories'] = $this->category;
        }

        /** @var Profile $profile */
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($this->username);

        // we create the file writer that will write (partially) the result file
        /** @var FileIOFactory $fileFactory */
        $fileFactory = $this->Plugin()->getFileIOFactory();
        $fileHelper = $fileFactory->createFileHelper();
        /** @var FileWriter $fileWriter */
        $fileWriter = $fileFactory->createFileWriter($postData, $fileHelper);

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()
            ->createDataTransformerChain($profile, array('isTree' => $fileWriter->hasTreeStructure()));

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        $post = $dataWorkflow->export($postData, $this->filePath);

        $message = $post['position'] . ' ' . $profile->getType() . ' exported successfully';

        $logger->write($message, 'false');

        $logData = new LogDataStruct(
            date("Y-m-d H:i:s"),
            $post['fileName'],
            $profile->getName(),
            $message,
            'true'
        );

        $logger->writeToFile($logData);

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
        /** @var FileReader $fileReader */
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData, null);

        if ($this->format === 'xml') {
            $tree = json_decode($this->profileEntity->getTree(), true);
            $fileReader->setTree($tree);
        }

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($this->profileEntity->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());

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

        $logger = $this->getLogger();

        // we create the file reader that will read the result file
        /** @var FileIOFactory $fileFactory */
        $fileFactory = $this->Plugin()->getFileIOFactory();
        $fileReader = $fileFactory->createFileReader($postData, null);

        //load profile
        /** @var Profile $profile */
        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        //setting up the batch size
        $postData['batchSize'] = $profile->getType() === 'articlesImages' ? 1 : 50;

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $logger);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($this->username);

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()
            ->createDataTransformerChain($profile, array('isTree' => $fileReader->hasTreeStructure()));

        $sessionState = $dataIO->getSessionState();

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);

        try {
            $post = $dataWorkflow->import($postData, $inputFile);

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
                    $outputFile = 'media/unknown/' . $pathInfo['filename'] . '-' . $key . '-tmp.csv';
                    $post['unprocessed'][] = array(
                        'profileName' => $key,
                        'fileName' => $outputFile
                    );
                    $this->afterImport($data, $key, $outputFile);
                }
            }

            $this->sessionId = $post['sessionId'];

            if (
                $dataSession->getTotalCount() > 0
                && ($dataSession->getTotalCount() == $post['position'])
                && $logger->getMessage() === null
            ) {
                $message = $post['position'] . ' ' . $post['adapter'] . ' imported successfully';

                $logger->write($message, 'false');

                $logDataStruct = new LogDataStruct(
                    date("Y-m-d H:i:s"),
                    $inputFile,
                    $profile->getName(),
                    $message,
                    'false'
                );

                $logger->writeToFile($logDataStruct);
            }

            return array('success' => true, 'data' => $post);
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

            throw $e;
        }
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
        /** @var FileIOFactory $fileFactory */
        $fileFactory = $this->Plugin()->getFileIOFactory();

        //loads hidden profile for article
        /** @var Profile $profile */
        $profile = $this->Plugin()->getProfileFactory()->loadHiddenProfile($profileName);

        $fileHelper = $fileFactory->createFileHelper();
        $fileWriter = $fileFactory->createFileWriter(array('format' => 'csv'), $fileHelper);

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()
            ->createDataTransformerChain($profile, array('isTree' => $fileWriter->hasTreeStructure()));

        $dataWorkflow = new DataWorkflow(null, $profile, $dataTransformerChain, $fileWriter);
        $dataWorkflow->saveUnprocessedData($data, $profileName, Shopware()->DocPath() . $outputFile);
    }

    /**
     * @return \Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    protected function Plugin()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return Shopware()->Container()->get('swag_import_export.logger');
    }
}
