<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Service\ImportService;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;

/**
 * Shopware ImportExport Plugin
 *
 * Import controller to handle all imports.
 */
class Shopware_Controllers_Backend_SwagImportExportImport extends \Shopware_Controllers_Backend_ExtJs
{
    private UploadPathProvider $uploadPathProvider;

    private ImportService $importService;

    private ProfileFactory $profileFactory;

    private SessionService $sessionService;

    public function __construct(
        UploadPathProvider $uploadPathProvider,
        ImportService $importService,
        ProfileFactory $profileFactory,
        SessionService $sessionService
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->importService = $importService;
        $this->profileFactory = $profileFactory;
        $this->sessionService = $sessionService;
    }

    public function initAcl(): void
    {
        $this->addAclPermission('prepareImport', 'import', 'Insuficient Permissions (prepareImport)');
        $this->addAclPermission('import', 'import', 'Insuficient Permissions (import)');
    }

    public function prepareImportAction(): void
    {
        $request = $this->Request();
        $profile = $this->profileFactory->loadProfile((int) $request->getParam('profileId'));

        $importFile = $this->uploadPathProvider->getRealPath($request->getParam('importFile'));

        $importRequest = new ImportRequest();
        $importRequest->setData([
            'sessionId' => $request->getParam('sessionId') ? (int) $request->getParam('sessionId') : null,
            'profileEntity' => $profile,
            'type' => 'import',
            'inputFileName' => $this->uploadPathProvider->getFileNameFromPath($importFile),
            'format' => $this->uploadPathProvider->getFileExtension($importFile),
        ]);

        if (empty($importRequest->inputFileName)) {
            $this->View()->assign(['success' => false, 'msg' => 'No valid file']);

            return;
        }

        try {
            $totalCount = $this->importService->prepareImport($importRequest);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);

            return;
        }

        $this->View()->assign([
            'success' => true,
            'position' => 0,
            'count' => $totalCount,
        ]);
    }

    public function importAction(): void
    {
        $request = $this->Request();
        $inputFile = $this->uploadPathProvider->getRealPath($request->getParam('importFile'));

        $importRequest = new ImportRequest();

        $config = $this->get('config');

        $profile = $this->profileFactory->loadProfile((int) $request->getParam('profileId'));

        $importRequest->setData(
            [
                'type' => 'import',
                'profileEntity' => $profile,
                'inputFileName' => $inputFile,
                'sessionId' => $request->getParam('sessionId') ? (int) $request->getParam('sessionId') : null,
                'limit' => [],
                'format' => \pathinfo($inputFile, \PATHINFO_EXTENSION),
                'batchSize' => $profile->getType() === 'articlesImages' ? 1 : (int) $config->getByNamespace('SwagImportExport', 'batch-size-import', 1000),
        ]
        );

        $session = $this->sessionService->createSession();

        try {
            $lastPosition = 0;
            foreach ($this->importService->import($importRequest, $session) as [$profileName, $position]) {
                if ($profileName === $importRequest->profileEntity->getName()) {
                    $lastPosition = $position;
                }
            }
            $resultData = [
                'importFile' => $importRequest->inputFileName,
                'position' => $lastPosition,
            ];

            $this->View()->assign([
                'success' => true,
                'data' => $resultData,
            ]);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
