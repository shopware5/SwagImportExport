<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

use Shopware\Bundle\MediaBundle\MediaService;
use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\DataTransformerFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\Factories\ProfileFactory;
use Shopware\Components\SwagImportExport\FileIO\FileReader;
use Shopware\Components\SwagImportExport\Logger\LogDataStruct;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\Service\Struct\ServiceHelperStruct;
use Shopware\Components\SwagImportExport\Transformers\DataTransformerChain;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\CustomModels\ImportExport\Session;

abstract class AbstractImportExportService
{
    /** @var DataFactory */
    protected $dataFactory;

    /** @var ProfileFactory */
    protected $profileFactory;

    /** @var FileIOFactory */
    protected $fileIOFactory;

    /** @var DataTransformerFactory */
    protected $dataTransformerFactory;

    /** @var Logger */
    protected $logger;

    /** @var UploadPathProvider */
    protected $uploadPathProvider;

    /** @var \Shopware_Components_Auth */
    protected $auth;

    /** @var MediaService */
    protected $mediaService;

    /**
     * @param ProfileFactory            $profileFactory
     * @param FileIOFactory             $fileIOFactory
     * @param DataFactory               $dataFactory
     * @param DataTransformerFactory    $dataTransformerFactory
     * @param Logger                    $logger
     * @param UploadPathProvider        $uploadPathProvider
     * @param \Shopware_Components_Auth $auth
     * @param MediaService              $mediaService
     */
    public function __construct(
        ProfileFactory $profileFactory,
        FileIOFactory $fileIOFactory,
        DataFactory $dataFactory,
        DataTransformerFactory $dataTransformerFactory,
        Logger $logger,
        UploadPathProvider $uploadPathProvider,
        \Shopware_Components_Auth $auth,
        MediaService $mediaService
    ) {
        $this->profileFactory = $profileFactory;
        $this->fileIOFactory = $fileIOFactory;
        $this->dataFactory = $dataFactory;
        $this->dataTransformerFactory = $dataTransformerFactory;
        $this->logger = $logger;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->auth = $auth;
        $this->mediaService = $mediaService;
    }

    /**
     * @param array $requestData
     *
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

    /**
     * @param array  $requestData
     * @param DataIO $dataIO
     */
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
     * @param Profile $profile
     * @param bool    $hasTreeStructure
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
     * @param string  $writeStatus
     * @param string  $filename
     * @param string  $profileName
     * @param string  $logMessage
     * @param string  $status
     * @param Session $session
     */
    protected function logProcessing($writeStatus, $filename, $profileName, $logMessage, $status, Session $session)
    {
        $this->logger->write($logMessage, $writeStatus, $session);

        $logDataStruct = new LogDataStruct(
            date('Y-m-d H:i:s'),
            $filename,
            $profileName,
            $logMessage,
            $status
        );

        $this->logger->writeToFile($logDataStruct);
    }

    /**
     * @param Profile $profile
     * @param $format
     *
     * @return FileReader
     */
    private function createFileReader(Profile $profile, $format)
    {
        $fileReader = $this->fileIOFactory->createFileReader($format);
        if ($format === 'xml') {
            $tree = json_decode($profile->getConfig('tree'), true);

            $fileReader->setTree($tree);
        }

        return $fileReader;
    }
}
