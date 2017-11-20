<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\Service\Struct\PreparationResultStruct;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class ImportService extends AbstractImportExportService implements ImportServiceInterface
{
    /**
     * @param array  $requestData
     * @param string $inputFileName
     *
     * @return PreparationResultStruct
     * @trows \Exception
     */
    public function prepareImport(array $requestData, $inputFileName)
    {
        $serviceHelpers = $this->buildServiceHelpers($requestData);

        $position = $serviceHelpers->getDataIO()->getSessionPosition();
        $position = $position == null ? 0 : $position;

        $totalCount = $serviceHelpers->getFileReader()->getTotalCount($this->uploadPathProvider->getRealPath($inputFileName));

        return new PreparationResultStruct($position, $totalCount);
    }

    /**
     * @param array  $requestData
     * @param array  $unprocessedFiles
     * @param string $inputFile
     *
     * @throws \Exception
     *
     * @return array
     */
    public function import(array $requestData, array $unprocessedFiles, $inputFile)
    {
        $serviceHelpers = $this->buildServiceHelpers($requestData);

        // set default batchsize for adapter
        $requestData['batchSize'] = $serviceHelpers->getProfile()->getType() === 'articlesImages' ? 1 : Shopware()->Config()->getByNamespace('SwagImportExport', 'batch-size-import');

        $this->initializeDataIO($serviceHelpers->getDataIO(), $requestData);

        $dataTransformerChain = $this->createDataTransformerChain($serviceHelpers->getProfile(), $serviceHelpers->getFileReader()->hasTreeStructure());

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

            if ($serviceHelpers->getSession()->getTotalCount() > 0 && ($serviceHelpers->getSession()->getTotalCount() == $resultData['position'])) {
                // unprocessed files
                $postProcessedData = null;
                if ($unprocessedFiles) {
                    $postProcessedData = $this->processData($unprocessedFiles);
                }

                if ($postProcessedData) {
                    unset($resultData['sessionId']);
                    unset($resultData['adapter']);

                    $resultData = array_merge($resultData, $postProcessedData);
                }

                if ($this->logger->getMessage() === null) {
                    $message = sprintf(
                        '%s %s %s',
                        $resultData['position'],
                        SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get($resultData['adapter']),
                        SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('import/success')
                    );
                    $session = $serviceHelpers->getSession()->getEntity();
                    $this->logProcessing('false', $inputFile, $serviceHelpers->getProfile()->getName(), $message, 'true', $session);
                }
            }

            unset($resultData['unprocessedData']);
            $resultData['unprocessedFiles'] = json_encode($unprocessedFiles);
            $resultData['importFile'] = $this->uploadPathProvider->getFileNameFromPath($resultData['importFile']);

            return $resultData;
        } catch (\Exception $e) {
            $session = $serviceHelpers->getSession()->getEntity();
            $this->logProcessing('true', $inputFile, $serviceHelpers->getProfile()->getName(), $e->getMessage(), 'false', $session);

            throw $e;
        }
    }

    /**
     * @param array  $unprocessedData
     * @param string $profileName
     * @param string $outputFile
     */
    protected function afterImport(array $unprocessedData, $profileName, $outputFile)
    {
        //loads hidden profile for article
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
     * @param array $unprocessedFiles
     *
     * @return array|bool
     */
    protected function processData(&$unprocessedFiles)
    {
        foreach ($unprocessedFiles as $hiddenProfile => $inputFile) {
            if (is_readable($inputFile)) {
                // renames
                $outputFile = str_replace('-tmp', '-swag', $inputFile);
                rename($inputFile, $outputFile);

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

        return false;
    }
}
