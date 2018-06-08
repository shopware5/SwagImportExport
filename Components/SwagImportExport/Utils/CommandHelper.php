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
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\CustomModels\ImportExport\Profile as ProfileEntity;
use Shopware\CustomModels\ImportExport\Repository;

class CommandHelper
{
    /**
     * @var ProfileEntity
     */
    protected $profileEntity;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $format;

    /**
     * @var string
     */
    protected $exportVariants;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $category;

    /**
     * @var int
     */
    protected $productStream;

    /**
     * @var int
     */
    protected $sessionId;

    /**
     * @var \Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    protected $plugin;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var int
     */
    protected $customerStream;

    /**
     * @param array $data
     *
     * @throws \RuntimeException
     */
    public function __construct(array $data)
    {
        $this->plugin = Shopware()->Plugins()->Backend()->SwagImportExport();
        $this->logger = Shopware()->Container()->get('swag_import_export.logger');

        if (!isset($data['profileEntity'])) {
            throw new \RuntimeException('No profile given!');
        }
        if (!isset($data['format'])) {
            throw new \RuntimeException('No format given!');
        }
        if (!isset($data['filePath']) || !is_dir(dirname($data['filePath']))) {
            throw new \RuntimeException('Invalid file path ' . $data['filePath']);
        }

        $this->profileEntity = $data['profileEntity'];
        $this->format = $data['format'];
        $this->filePath = $data['filePath'];

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

        if (!empty($data['category'])) {
            $this->category = $data['category'];
        }

        if (!empty($data['productStream'])) {
            $this->productStream = $data['productStream'];
        }

        if (!empty($data['customerStream'])) {
            $this->customerStream = $data['customerStream'];
        }
    }

    /**
     * Tries to find profile by given name
     *
     * @param string     $filename
     * @param Repository $repository
     *
     * @return bool|ProfileEntity
     */
    public static function findProfileByName($filename, Repository $repository)
    {
        $parts = explode('.', $filename);

        foreach ($parts as $part) {
            $part = strtolower($part);
            /** @var ProfileEntity $profileEntity */
            $profileEntity = $repository->findOneBy(['name' => $part]);

            if ($profileEntity !== null) {
                return $profileEntity;
            }
        }

        return false;
    }

    /**
     * Prepares export
     *
     * @return array
     */
    public function prepareExport()
    {
        $this->sessionId = null;
        $postData = [
            'sessionId' => $this->sessionId,
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'export',
            'format' => $this->format,
            'filter' => [],
            'limit' => [
                'limit' => $this->limit,
                'offset' => $this->offset,
            ],
        ];

        if ($this->exportVariants) {
            $postData['filter']['variants'] = $this->exportVariants;
        }

        if ($this->category) {
            $postData['filter']['categories'] = $this->category;
        }

        if ($this->productStream) {
            $postData['filter']['productStreamId'] = $this->productStream;
        }

        if ($this->customerStream) {
            $postData['filter']['customerStreamId'] = $this->customerStream;
        }

        /** @var Profile $profile */
        $profile = $this->plugin->getProfileFactory()->loadProfile($postData);

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->plugin->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->logger);

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

        return [
            'position' => $position,
            'count' => count($ids),
        ];
    }

    /**
     * Executes export action
     *
     * @return array
     */
    public function exportAction()
    {
        $postData = [
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'export',
            'format' => $this->format,
            'sessionId' => $this->sessionId,
            'fileName' => basename($this->filePath),
            'filter' => [],
            'limit' => [
                'limit' => $this->limit,
                'offset' => $this->offset,
            ],
        ];

        if ($this->exportVariants) {
            $postData['filter']['variants'] = $this->exportVariants;
        }

        if ($this->category) {
            $postData['filter']['categories'] = $this->category;
        }

        if ($this->productStream) {
            $postData['filter']['productStreamId'] = $this->productStream;
        }
        if ($this->customerStream) {
            $postData['filter']['customerStreamId'] = $this->customerStream;
        }

        /** @var Profile $profile */
        $profile = $this->plugin->getProfileFactory()->loadProfile($postData);

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->plugin->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->logger);

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
        $fileFactory = $this->plugin->getFileIOFactory();

        /** @var FileWriter $fileWriter */
        $fileWriter = $fileFactory->createFileWriter($postData['format']);

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->plugin->getDataTransformerFactory()
            ->createDataTransformerChain($profile, ['isTree' => $fileWriter->hasTreeStructure()]);

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        $resultData = $dataWorkflow->export($postData, $this->filePath);

        $message = sprintf(
            '%s %s %s',
            $resultData['position'],
            SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get('type/' . $profile->getType()),
            SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('export/success')
        );

        $this->logger->write($message, 'false', $dataSession->getEntity());

        $logData = new LogDataStruct(
            date('Y-m-d H:i:s'),
            $resultData['fileName'],
            $profile->getName(),
            $message,
            'true'
        );

        $this->logger->writeToFile($logData);

        $this->sessionId = $resultData['sessionId'];

        return $resultData;
    }

    /**
     * Prepares import
     *
     * @throws \Exception
     *
     * @return array
     */
    public function prepareImport()
    {
        $this->sessionId = null;
        $postData = [
            'sessionId' => $this->sessionId,
            'profileId' => (int) $this->profileEntity->getId(),
            'type' => 'import',
            'format' => $this->format,
            'file' => $this->filePath,
        ];

        //get file format
        $inputFileName = $postData['file'];

        //get profile type
        $postData['adapter'] = $this->profileEntity->getType();

        // we create the file reader that will read the result file
        /** @var FileReader $fileReader */
        $fileReader = $this->plugin->getFileIOFactory()->createFileReader($postData['format']);

        if ($this->format === 'xml') {
            $tree = json_decode($this->profileEntity->getTree(), true);
            $fileReader->setTree($tree);
        }

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->plugin->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($this->profileEntity->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->logger);

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $totalCount = $fileReader->getTotalCount($inputFileName);

        return [
            'success' => true,
            'position' => $position,
            'count' => $totalCount,
        ];
    }

    /**
     * Executes import action
     *
     * @throws \Exception
     *
     * @return array
     */
    public function importAction()
    {
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = Shopware()->Container()->get('swag_import_export.upload_path_provider');

        $postData = [
            'type' => 'import',
            'profileId' => (int) $this->profileEntity->getId(),
            'importFile' => $this->filePath,
            'sessionId' => $this->sessionId,
            'format' => $this->format,
            'columnOptions' => null,
            'limit' => [],
            'filter' => null,
            'max_record_count' => null,
        ];

        $inputFile = $postData['importFile'];

        // we create the file reader that will read the result file
        /** @var FileIOFactory $fileFactory */
        $fileFactory = $this->plugin->getFileIOFactory();
        $fileReader = $fileFactory->createFileReader($postData['format']);

        //load profile
        /** @var Profile $profile */
        $profile = $this->plugin->getProfileFactory()->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        //setting up the batch size
        $postData['batchSize'] = $profile->getType() === 'articlesImages' ? 1 : 50;

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->plugin->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->logger);

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'];
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($this->username);

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->plugin->getDataTransformerFactory()
            ->createDataTransformerChain($profile, ['isTree' => $fileReader->hasTreeStructure()]);

        $sessionState = $dataIO->getSessionState();

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);

        try {
            $resultData = $dataWorkflow->import($postData, $inputFile);

            if (isset($resultData['unprocessedData']) && $resultData['unprocessedData']) {
                $data = [
                    'data' => $resultData['unprocessedData'],
                    'session' => [
                        'prevState' => $sessionState,
                        'currentState' => $dataIO->getSessionState(),
                    ],
                ];

                $pathInfo = pathinfo($inputFile);

                foreach ($data['data'] as $key => $value) {
                    $outputFile = $uploadPathProvider->getRealPath(
                        $pathInfo['filename'] . '-' . $key . '-tmp.csv'
                    );

                    $post['unprocessed'][] = [
                        'profileName' => $key,
                        'fileName' => $outputFile,
                    ];
                    $this->afterImport($data, $key, $outputFile);
                }
            }

            $this->sessionId = $resultData['sessionId'];

            $dataSessionTotalCount = $dataSession->getTotalCount();
            if ($dataSessionTotalCount > 0
                && ($dataSessionTotalCount == $resultData['position'])
                && $this->logger->getMessage() === null
            ) {
                $message = sprintf(
                    '%s %s %s',
                    $resultData['position'],
                    SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get($resultData['adapter']),
                    SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('import/success')
                );

                $this->logger->write($message, 'false', $dataSession->getEntity());

                $logDataStruct = new LogDataStruct(
                    date('Y-m-d H:i:s'),
                    $inputFile,
                    $profile->getName(),
                    $message,
                    'false'
                );

                $this->logger->writeToFile($logDataStruct);
            }

            return ['success' => true, 'data' => $resultData];
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage(), 'true', $dataSession->getEntity());

            $logDataStruct = new LogDataStruct(
                date('Y-m-d H:i:s'),
                $inputFile,
                $profile->getName(),
                $e->getMessage(),
                'false'
            );

            $this->logger->writeToFile($logDataStruct);

            throw $e;
        }
    }

    /**
     * Saves unprocessed data to csv file
     *
     * @param array  $data
     * @param string $profileName
     * @param string $outputFile
     */
    protected function afterImport($data, $profileName, $outputFile)
    {
        /** @var FileIOFactory $fileFactory */
        $fileFactory = $this->plugin->getFileIOFactory();

        //loads hidden profile for article
        /** @var Profile $profile */
        $profile = $this->plugin->getProfileFactory()->loadHiddenProfile($profileName);

        $fileWriter = $fileFactory->createFileWriter('csv');

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->plugin->getDataTransformerFactory()
            ->createDataTransformerChain($profile, ['isTree' => $fileWriter->hasTreeStructure()]);

        $dataWorkflow = new DataWorkflow(null, $profile, $dataTransformerChain, $fileWriter);
        $dataWorkflow->saveUnprocessedData($data, $profileName, $outputFile);
    }
}
