<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Logger\LoggerInterface;
use SwagImportExport\Components\Providers\FileIOProvider;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\SnippetsHelper;

class ImportService implements ImportServiceInterface
{
    private const SUPPORTED_UNPROCESSED_DATA_PROFILE_TYPES = [
        DataDbAdapter::PRODUCT_ADAPTER,
        DataDbAdapter::PRODUCT_IMAGE_ADAPTER,
    ];

    private UploadPathProvider $uploadPathProvider;

    private LoggerInterface $logger;

    private FileIOProvider $fileIOFactory;

    private DataWorkflow $dataWorkflow;

    private ProfileFactory $profileFactory;

    private ModelManager $modelManager;

    public function __construct(
        FileIOProvider $fileIOFactory,
        UploadPathProvider $uploadPathProvider,
        LoggerInterface $logger,
        DataWorkflow $dataWorkflow,
        ProfileFactory $profileFactory,
        ModelManager $modelManager
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->logger = $logger;
        $this->fileIOFactory = $fileIOFactory;
        $this->dataWorkflow = $dataWorkflow;
        $this->profileFactory = $profileFactory;
        $this->modelManager = $modelManager;
    }

    public function prepareImport(ImportRequest $importRequest): int
    {
        // we create the file reader that will read the result file
        $fileReader = $this->fileIOFactory->getFileReader($importRequest->format);

        if ($importRequest->format === 'xml') {
            $tree = \json_decode($importRequest->profileEntity->getEntity()->getTree(), true);
            $fileReader->setTree($tree);
        }

        return $fileReader->getTotalCount($importRequest->inputFile);
    }

    public function import(ImportRequest $importRequest, Session $session): \Generator
    {
        yield from $this->doImport($importRequest, $session);
        $this->modelManager->clear();
    }

    public function prepareImportOfUnprocessedData(ImportRequest $request): ?array
    {
        // loops the unprocessed data
        $pathInfoBaseName = \pathinfo($request->inputFile, \PATHINFO_BASENAME);
        foreach (self::SUPPORTED_UNPROCESSED_DATA_PROFILE_TYPES as $profileType) {
            $tmpFileName = $pathInfoBaseName . '-' . $profileType . self::UNPROCESSED_DATA_FILE_ENDING;
            $tmpFile = $this->uploadPathProvider->getRealPath($tmpFileName);

            if (!\file_exists($tmpFile)) {
                continue;
            }

            $profile = $this->profileFactory->loadHiddenProfile($profileType);

            $fileReader = $this->fileIOFactory->getFileReader('csv');
            $totalCount = $fileReader->getTotalCount($tmpFile);

            return [
                'importFile' => $tmpFileName,
                'profileId' => $profile->getId(),
                'count' => $totalCount,
                'position' => 0,
                'load' => true,
            ];
        }

        return null;
    }

    private function doImport(ImportRequest $request, Session $session): \Generator
    {
        do {
            try {
                $resultData = $this->dataWorkflow->import($request, $session);

                if (!empty($resultData['unprocessedData'])) {
                    foreach ($resultData['unprocessedData'] as $profileName => $value) {
                        $outputFile = $this->uploadPathProvider->getRealPath(
                            $this->uploadPathProvider->getFileNameFromPath($request->inputFile) . '-' . $profileName . self::UNPROCESSED_DATA_FILE_ENDING
                        );

                        $this->dataWorkflow->saveUnprocessedData($resultData['unprocessedData'], $profileName, $outputFile, $session->getState());
                    }
                }

                $message = \sprintf(
                    '%s %s %s',
                    $resultData['position'],
                    SnippetsHelper::getNamespace('backend/swag_import_export/default_profiles')->get($resultData['adapter']),
                    SnippetsHelper::getNamespace('backend/swag_import_export/log')->get('import/success')
                );

                $this->logger->logProcessing(
                    'false',
                    $request->inputFile,
                    $request->profileEntity->getName(),
                    $message,
                    'false',
                    $session
                );

                yield [$request->profileEntity->getName(), $resultData['position']];
            } catch (\Exception $e) {
                $this->logger->logProcessing('true', $request->inputFile, $request->profileEntity->getName(), $e->getMessage(), 'false', $session);

                throw $e;
            }
        } while ($session->getState() !== Session::SESSION_CLOSE);
    }
}
