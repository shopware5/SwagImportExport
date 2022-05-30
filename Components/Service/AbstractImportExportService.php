<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use SwagImportExport\Components\DataIO;
use SwagImportExport\Components\Factories\DataFactory;
use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Components\Factories\FileIOFactory;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\Logger\LogDataStruct;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Service\Struct\ServiceHelperStruct;
use SwagImportExport\Components\Transformers\DataTransformerChain;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\CustomModels\Session;

abstract class AbstractImportExportService
{
    protected DataFactory $dataFactory;

    protected ProfileFactory $profileFactory;

    protected FileIOFactory $fileIOFactory;

    protected DataTransformerFactory $dataTransformerFactory;

    protected Logger $logger;

    protected UploadPathProvider $uploadPathProvider;

    protected \Shopware_Components_Auth $auth;

    protected MediaServiceInterface $mediaService;

    protected \Shopware_Components_Config $config;

    public function __construct(
        ProfileFactory $profileFactory,
        FileIOFactory $fileIOFactory,
        DataFactory $dataFactory,
        DataTransformerFactory $dataTransformerFactory,
        Logger $logger,
        UploadPathProvider $uploadPathProvider,
        \Shopware_Components_Auth $auth,
        MediaServiceInterface $mediaService,
        \Shopware_Components_Config $config
    ) {
        $this->profileFactory = $profileFactory;
        $this->fileIOFactory = $fileIOFactory;
        $this->dataFactory = $dataFactory;
        $this->dataTransformerFactory = $dataTransformerFactory;
        $this->logger = $logger;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->auth = $auth;
        $this->mediaService = $mediaService;
        $this->config = $config;
    }

    /**
     * @return ServiceHelperStruct
     */
    protected function buildServiceHelpers(array $requestData)
    {
        $profile = $this->profileFactory->loadProfile($requestData);
        $session = $this->dataFactory->loadSession($requestData);
        $dbAdapter = $this->dataFactory->createDbAdapter($profile->getType());
        $fileReader = $this->createFileReader($profile, $requestData['format']);
        $fileWriter = $this->fileIOFactory->createFileWriter($requestData['format']);
        $dataIO = $this->dataFactory->createDataIO($dbAdapter, $session, $this->logger);

        return new ServiceHelperStruct(
            $profile,
            $session,
            $dbAdapter,
            $fileReader,
            $fileWriter,
            $dataIO
        );
    }

    protected function initializeDataIO(DataIO $dataIO, array $requestData)
    {
        $colOpts = $this->dataFactory->createColOpts($requestData['columnOptions']);
        $limit = $this->dataFactory->createLimit($requestData['limit']);
        $filter = $this->dataFactory->createFilter($requestData['filter']);
        $maxRecordCount = $requestData['max_record_count'];
        $type = $requestData['type'];
        $format = $requestData['format'];
        $username = $this->auth->getIdentity()->username;

        $dataIO->initialize($colOpts, $limit, $filter, $type, $format, $maxRecordCount);
        $dataIO->setUsername($username);
    }

    /**
     * @param bool $hasTreeStructure
     *
     * @return DataTransformerChain
     */
    protected function createDataTransformerChain(Profile $profile, $hasTreeStructure)
    {
        $dataTransformerChain = $this->dataTransformerFactory->createDataTransformerChain(
            $profile,
            ['isTree' => $hasTreeStructure]
        );

        return $dataTransformerChain;
    }

    /**
     * @param string $writeStatus
     * @param string $filename
     * @param string $profileName
     * @param string $logMessage
     * @param string $status
     */
    protected function logProcessing($writeStatus, $filename, $profileName, $logMessage, $status, Session $session)
    {
        $this->logger->write([$logMessage], $writeStatus, $session);

        $logDataStruct = new LogDataStruct(
            \date('Y-m-d H:i:s'),
            $filename,
            $profileName,
            $logMessage,
            $status
        );

        $this->logger->writeToFile($logDataStruct);
    }

    /**
     * @param string $format
     *
     * @return FileReader
     */
    private function createFileReader(Profile $profile, $format)
    {
        $fileReader = $this->fileIOFactory->createFileReader($format);
        if ($format === 'xml') {
            $tree = \json_decode($profile->getConfig('tree'), true);

            $fileReader->setTree($tree);
        }

        return $fileReader;
    }
}
