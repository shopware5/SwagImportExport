<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

use Doctrine\DBAL\Connection;
use SwagImportExport\Components\DataWorkflow;
use SwagImportExport\Components\Factories\DataFactory;
use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Components\Factories\FileIOFactory;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\FileIO\FileWriter;
use SwagImportExport\Components\Logger\LogDataStruct;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Transformers\DataTransformerChain;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\CustomModels\Profile as ProfileEntity;
use SwagImportExport\CustomModels\ProfileRepository;

class CommandHelper
{
    protected ProfileEntity $profileEntity;

    protected string $filePath;

    protected string $format;

    protected ?string $exportVariants = null;

    protected ?int $limit = null;

    protected ?int $offset = null;

    protected ?string $dateFrom = null;

    protected ?string $dateTo = null;

    protected ?string $username = null;

    protected ?string $category = null;

    protected ?int $productStream = null;

    protected ?int $sessionId = null;

    protected Logger $logger;

    protected ?int $customerStream = null;

    protected Connection $connection;

    private ProfileFactory $profileFactory;

    private DataFactory $dataFactory;

    private FileIOFactory $fileIoFactory;

    private DataTransformerFactory $dataTransformationFactory;

    /**
     * @throws \RuntimeException
     */
    public function __construct(array $data)
    {
        $this->profileFactory = Shopware()->Container()->get(ProfileFactory::class);
        $this->dataFactory = Shopware()->Container()->get(DataFactory::class);
        $this->fileIoFactory = Shopware()->Container()->get(FileIOFactory::class);
        $this->dataTransformationFactory = Shopware()->Container()->get(DataTransformerFactory::class);
        $this->logger = Shopware()->Container()->get(Logger::class);
        $this->connection = Shopware()->Container()->get('dbal_connection');

        if (!isset($data['profileEntity'])) {
            throw new \RuntimeException('No profile given!');
        }
        if (!isset($data['format'])) {
            throw new \RuntimeException('No format given!');
        }
        if (!isset($data['filePath']) || !\is_dir(\dirname($data['filePath']))) {
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

        if (isset($data['dateFrom'])) {
            $this->dateFrom = $data['dateFrom'];
        }

        if (isset($data['dateTo'])) {
            $this->dateTo = $data['dateTo'];
        }

        if (isset($data['username'])) {
            $this->username = $data['username'];
        }

        if (!empty($data['category'])) {
            $this->category = $data['category'];
        }

        if (!empty($data['productStream'])) {
            if (\is_array($data['productStream'])) {
                $data['productStream'] = \array_shift($data['productStream']);
            }

            $this->productStream = $this->getProductStreamIdByName($data['productStream']);
        }

        if (!empty($data['customerStream'])) {
            $this->customerStream = $data['customerStream'];
        }
    }

    /**
     * Tries to find profile by given name
     *
     * @param string $filename
     *
     * @return bool|ProfileEntity
     */
    public static function findProfileByName($filename, ProfileRepository $repository)
    {
        $parts = \explode('.', $filename);

        foreach ($parts as $part) {
            $part = \strtolower($part);
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

        if ($this->dateFrom) {
            $postData['filter']['dateFrom'] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $postData['filter']['dateTo'] = $this->dateTo;
        }

        /** @var Profile $profile */
        $profile = $this->profileFactory->loadProfile($postData);

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->dataFactory;

        $dbAdapter = $dataFactory->createDbAdapter($profile->getType());
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->logger);

        $colOpts = $dataFactory->createColOpts('');
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $maxRecordCount = $postData['max_record_count'] ?? 0;
        $type = $postData['type'];
        $format = $postData['format'];

        $dataIO->initialize($colOpts, $limit, $filter, $maxRecordCount, $type, $format);

        $ids = $dataIO->preloadRecordIds()->getRecordIds();

        $position = $dataIO->getSessionPosition();
        $position = $position == null ? 0 : $position;

        return [
            'position' => $position,
            'count' => \count($ids),
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
            'fileName' => \basename($this->filePath),
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

        if ($this->dateFrom) {
            $postData['filter']['dateFrom'] = $this->dateFrom;
        }

        if ($this->dateTo) {
            $postData['filter']['dateTo'] = $this->dateTo;
        }

        /** @var Profile $profile */
        $profile = $this->profileFactory->loadProfile($postData);

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->dataFactory;

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
        $fileFactory = $this->fileIoFactory;

        /** @var FileWriter $fileWriter */
        $fileWriter = $fileFactory->createFileWriter($postData['format']);

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->dataTransformationFactory
            ->createDataTransformerChain($profile, ['isTree' => $fileWriter->hasTreeStructure()]);

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        $resultData = $dataWorkflow->export($postData, $this->filePath);

        $message = \sprintf(
            '%s %s %s',
            $resultData['position'],
            SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get('type/' . $profile->getType()),
            SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('export/success')
        );

        $this->logger->write([$message], 'false', $dataSession->getEntity());

        $logData = new LogDataStruct(
            \date('Y-m-d H:i:s'),
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
        $fileReader = $this->fileIoFactory->createFileReader($postData['format']);

        if ($this->format === 'xml') {
            $tree = \json_decode($this->profileEntity->getTree(), true);
            $fileReader->setTree($tree);
        }

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->dataFactory;

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
            'filter' => [],
            'max_record_count' => null,
        ];

        $inputFile = $postData['importFile'];

        // we create the file reader that will read the result file
        /** @var FileIOFactory $fileFactory */
        $fileFactory = $this->fileIoFactory;
        $fileReader = $fileFactory->createFileReader($postData['format']);

        //load profile
        /** @var Profile $profile */
        $profile = $this->profileFactory->loadProfile($postData);

        //get profile type
        $postData['adapter'] = $profile->getType();

        //setting up the batch size
        $postData['batchSize'] = $profile->getType() === 'articlesImages' ? 1 : 50;

        /** @var DataFactory $dataFactory */
        $dataFactory = $this->dataFactory;

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
        $dataTransformerChain = $this->dataTransformationFactory
            ->createDataTransformerChain($profile, ['isTree' => $fileReader->hasTreeStructure()]);

        $sessionState = $dataIO->getSessionState();

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);

        try {
            $resultData = $dataWorkflow->import($postData, $inputFile);

            if (isset($resultData['unprocessedData']) && $resultData['unprocessedData']) {
                $unprocessedData = [
                    'data' => $resultData['unprocessedData'],
                    'session' => [
                        'prevState' => $sessionState,
                        'currentState' => $dataIO->getSessionState(),
                    ],
                ];

                foreach ($unprocessedData['data'] as $profileName => $value) {
                    $outputFile = $uploadPathProvider->getRealPath(
                        $uploadPathProvider->getFileNameFromPath($inputFile) . '-' . $profileName . '-tmp.csv'
                    );

                    $this->afterImport($unprocessedData, $profileName, $outputFile);
                    $unprocessedFiles[$profileName] = $outputFile;
                }
            }

            $this->sessionId = $resultData['sessionId'];

            $dataSessionTotalCount = $dataSession->getTotalCount();
            if ($dataSessionTotalCount > 0
                && ($dataSessionTotalCount == $resultData['position'])
                && $this->logger->getMessage() === null
            ) {
                $message = \sprintf(
                    '%s %s %s',
                    $resultData['position'],
                    SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get($resultData['adapter']),
                    SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('import/success')
                );

                $this->logger->write([$message], 'false', $dataSession->getEntity());

                $logDataStruct = new LogDataStruct(
                    \date('Y-m-d H:i:s'),
                    $inputFile,
                    $profile->getName(),
                    $message,
                    'false'
                );

                $this->logger->writeToFile($logDataStruct);
            }

            return ['success' => true, 'data' => $resultData];
        } catch (\Exception $e) {
            $this->logger->write([$e->getMessage()], 'true', $dataSession->getEntity());

            $logDataStruct = new LogDataStruct(
                \date('Y-m-d H:i:s'),
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
        $fileFactory = $this->fileIoFactory;

        //loads hidden profile for article
        /** @var Profile $profile */
        $profile = $this->profileFactory->loadHiddenProfile($profileName);

        $fileWriter = $fileFactory->createFileWriter('csv');

        /** @var DataTransformerChain $dataTransformerChain */
        $dataTransformerChain = $this->dataTransformationFactory
            ->createDataTransformerChain($profile, ['isTree' => $fileWriter->hasTreeStructure()]);

        $dataWorkflow = new DataWorkflow(null, $profile, $dataTransformerChain, $fileWriter);
        $dataWorkflow->saveUnprocessedData($data, $profileName, $outputFile);
    }

    /**
     * @throws \RuntimeException
     *
     * @return int|null
     */
    private function getProductStreamIdByName($productStreamName)
    {
        $tempProductStreamName = (int) $productStreamName;
        if ($tempProductStreamName) {
            return $productStreamName;
        }

        if (!$productStreamName) {
            return null;
        }

        $id = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_product_streams')
            ->where('name LIKE :productStreamName')
            ->setParameter('productStreamName', $productStreamName)
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        $idAmount = \count($id);
        if ($idAmount > 1) {
            throw new \RuntimeException(\sprintf('There are %d streams with the name: %s. Please use the stream id.', $idAmount, $productStreamName));
        }

        if ($idAmount < 1) {
            throw new \RuntimeException(\sprintf('There are no streams with the name: %s', $productStreamName));
        }

        return \array_shift($id);
    }
}
