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
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Providers\FileIOProvider;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\SnippetsHelper;

class ImportService implements ImportServiceInterface
{
    private UploadPathProvider $uploadPathProvider;

    private Logger $logger;

    private FileIOProvider $fileIOFactory;

    private DataWorkflow $dataWorkflow;

    private ProfileFactory $profileFactory;

    private ModelManager $modelManager;

    private SessionService $sessionService;

    public function __construct(
        FileIOProvider $fileIOFactory,
        UploadPathProvider $uploadPathProvider,
        Logger $logger,
        DataWorkflow $dataWorkflow,
        ProfileFactory $profileFactory,
        ModelManager $modelManager,
        SessionService $sessionService
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->logger = $logger;
        $this->fileIOFactory = $fileIOFactory;
        $this->dataWorkflow = $dataWorkflow;
        $this->profileFactory = $profileFactory;
        $this->modelManager = $modelManager;
        $this->sessionService = $sessionService;
    }

    public function prepareImport(ImportRequest $request): int
    {
        // we create the file reader that will read the result file
        /** @var FileReader $fileReader */
        $fileReader = $this->fileIOFactory->getFileReader($request->format);

        if ($request->format === 'xml') {
            $tree = \json_decode($request->profileEntity->getEntity()->getTree(), true);
            $fileReader->setTree($tree);
        }

        return $fileReader->getTotalCount($request->inputFile);
    }

    public function import(ImportRequest $request, Session $session): \Generator
    {
        yield from $this->doImport($request, $session);
        $this->modelManager->clear();
        foreach ($this->importUnprocessedData($request) as $nth) {
            // nth
        }
    }

    protected function afterImport(array $unprocessedData, string $profileName, string $outputFile, string $prevState): void
    {
        $this->dataWorkflow->saveUnprocessedData($unprocessedData, $profileName, $outputFile, $prevState);
    }

    protected function importUnprocessedData(ImportRequest $request): \Generator
    {
        $profilesMapper = ['articles', 'articlesImages'];

        // loops the unprocessed data
        $pathInfo = \pathinfo($request->inputFile);
        foreach ($profilesMapper as $profileName) {
            $tmpFile = $this->uploadPathProvider->getRealPath(
                $pathInfo['basename'] . '-' . $profileName . '-swag.csv'
            );

            if (!\file_exists($tmpFile)) {
                continue;
            }

            $profile = $this->profileFactory->loadHiddenProfile($profileName);

            $innerSession = $this->sessionService->createSession();

            $subRequest = new ImportRequest();
            $subRequest->setData(
                [
                    'profileEntity' => $profile,
                    'inputFile' => $tmpFile,
                    'format' => 'csv',
                    'username' => $request->username,
                    'batchSize' => $profile->getEntity()->getType() === 'articlesImages' ? 1 : $request->batchSize,
                ]
            );

            yield from $this->doImport($subRequest, $innerSession);
        }
    }

    private function doImport(ImportRequest $request, Session $session): \Generator
    {
        $sessionState = $session->getState();

        do {
            try {
                $resultData = $this->dataWorkflow->import($request, $session);

                if (!empty($resultData['unprocessedData'])) {
                    foreach ($resultData['unprocessedData'] as $profileName => $value) {
                        $outputFile = $this->uploadPathProvider->getRealPath(
                            $this->uploadPathProvider->getFileNameFromPath($request->inputFile) . '-' . $profileName . '-swag.csv'
                        );

                        $this->afterImport($resultData['unprocessedData'], $profileName, $outputFile, $sessionState);
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
