<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\DataWorkflow;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Components\Factories\FileIOFactory;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Service\Struct\PreparationResultStruct;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\SnippetsHelper;

class ImportService implements ImportServiceInterface
{
    private ImportExportServiceHelper $importExportServiceHelper;

    private UploadPathProvider $uploadPathProvider;

    private ProfileFactory $profileFactory;

    private \Shopware_Components_Config $config;

    private Logger $logger;

    private FileIOFactory $fileIOFactory;

    private DataTransformerFactory $dataTransformerFactory;

    public function __construct(
        ImportExportServiceHelper $importExportServiceHelper,
        FileIOFactory $fileIOFactory,
        DataTransformerFactory $dataTransformerFactory,
        UploadPathProvider $uploadPathProvider,
        ProfileFactory $profileFactory,
        Logger $logger,
        \Shopware_Components_Config $config
    ) {
        $this->importExportServiceHelper = $importExportServiceHelper;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->logger = $logger;
        $this->fileIOFactory = $fileIOFactory;
        $this->dataTransformerFactory = $dataTransformerFactory;
    }

    public function prepareImport(array $requestData, string $inputFileName): PreparationResultStruct
    {
        $serviceHelpers = $this->importExportServiceHelper->buildServiceHelpers($requestData);

        $position = $serviceHelpers->getDataIO()->getSessionPosition();

        $totalCount = $serviceHelpers->getFileReader()->getTotalCount($this->uploadPathProvider->getRealPath($inputFileName));

        return new PreparationResultStruct($position, $totalCount);
    }

    public function import(array $requestData, array $unprocessedFiles, string $inputFile): array
    {
        $serviceHelpers = $this->importExportServiceHelper->buildServiceHelpers($requestData);

        // set default batchsize for adapter
        $requestData['batchSize'] = $serviceHelpers->getProfile()->getType() === 'articlesImages' ? 1 : $this->config->getByNamespace('SwagImportExport', 'batch-size-import');

        $this->importExportServiceHelper->initializeDataIO($serviceHelpers->getDataIO(), $requestData);

        $dataTransformerChain = $this->importExportServiceHelper->createDataTransformerChain($serviceHelpers->getProfile(), $serviceHelpers->getFileReader()->hasTreeStructure());

        $sessionState = $serviceHelpers->getSession()->getState();

        $dataWorkflow = new DataWorkflow($serviceHelpers->getDataIO(), $serviceHelpers->getProfile(), $dataTransformerChain, $serviceHelpers->getFileReader());

        try {
            $resultData = $dataWorkflow->import($requestData, $inputFile);

            if (!empty($resultData['unprocessedData'])) {
                $unprocessedData = [
                    'data' => $resultData['unprocessedData'],
                    'session' => [
                        'prevState' => $sessionState,
                        'currentState' => $serviceHelpers->getDataIO()->getSessionState(),
                    ],
                ];

                foreach ($unprocessedData['data'] as $profileName => $value) {
                    $outputFile = $this->uploadPathProvider->getRealPath(
                        $this->uploadPathProvider->getFileNameFromPath($inputFile) . '-' . $profileName . '-tmp.csv'
                    );
                    $this->afterImport($unprocessedData, $profileName, $outputFile);
                    $unprocessedFiles[$profileName] = $outputFile;
                }
            }

            if ($serviceHelpers->getSession()->getTotalCount() > 0 && ($serviceHelpers->getSession()->getTotalCount() === $resultData['position'])) {
                // unprocessed files
                $postProcessedData = null;
                if ($unprocessedFiles) {
                    $postProcessedData = $this->processData($unprocessedFiles);
                }

                if (!empty($postProcessedData)) {
                    unset($resultData['sessionId']);
                    unset($resultData['adapter']);

                    $resultData = \array_merge($resultData, $postProcessedData);
                }

                if ($this->logger->getMessage() === null) {
                    $message = \sprintf(
                        '%s %s %s',
                        $resultData['position'],
                        SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get($resultData['adapter']),
                        SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('import/success')
                    );
                    $session = $serviceHelpers->getSession()->getEntity();
                    $this->importExportServiceHelper->logProcessing('false', $inputFile, $serviceHelpers->getProfile()->getName(), $message, 'true', $session);
                }
            }

            unset($resultData['unprocessedData']);
            $resultData['unprocessedFiles'] = \json_encode($unprocessedFiles);
            $resultData['importFile'] = $this->uploadPathProvider->getFileNameFromPath($resultData['importFile']);

            return $resultData;
        } catch (\Exception $e) {
            $session = $serviceHelpers->getSession()->getEntity();
            $this->importExportServiceHelper->logProcessing('true', $inputFile, $serviceHelpers->getProfile()->getName(), $e->getMessage(), 'false', $session);

            throw $e;
        }
    }

    protected function afterImport(array $unprocessedData, string $profileName, string $outputFile): void
    {
        // loads hidden profile for article
        $profile = $this->profileFactory->loadHiddenProfile($profileName);

        $fileWriter = $this->fileIOFactory->createFileWriter('csv');

        $dataTransformerChain = $this->dataTransformerFactory->createDataTransformerChain(
            $profile,
            ['isTree' => $fileWriter->hasTreeStructure()]
        );

        $dataWorkflow = new DataWorkflow(null, $profile, $dataTransformerChain, $fileWriter);
        $dataWorkflow->saveUnprocessedData($unprocessedData, $profileName, $outputFile);
    }

    /**
     * Checks for unprocessed data
     * Returns unprocessed file for import
     *
     * @return array{importFile: string, profileId: int, count: int, position: int, format: string, load: bool}|array{}
     */
    protected function processData(array &$unprocessedFiles): array
    {
        foreach ($unprocessedFiles as $hiddenProfile => $inputFile) {
            if (\is_readable($inputFile)) {
                // renames
                $outputFile = \str_replace('-tmp', '-swag', $inputFile);
                \rename($inputFile, $outputFile);

                $profile = $this->profileFactory->loadHiddenProfile($hiddenProfile);
                $profileId = $profile->getId();

                $fileReader = $this->fileIOFactory->createFileReader('csv');
                $totalCount = $fileReader->getTotalCount($outputFile);

                unset($unprocessedFiles[$hiddenProfile]);

                $postData = [
                    'importFile' => $outputFile,
                    'profileId' => $profileId,
                    'count' => $totalCount,
                    'position' => 0,
                    'format' => 'csv',
                    'load' => true,
                ];

                if ($hiddenProfile === DataDbAdapter::ARTICLE_IMAGE_ADAPTER) {
                    // set to one because of thumbnail generation memory cost
                    $postData['batchSize'] = 1;
                }

                return $postData;
            }
        }

        return [];
    }
}
