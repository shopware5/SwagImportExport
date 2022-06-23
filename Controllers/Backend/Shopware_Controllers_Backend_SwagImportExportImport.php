<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use SwagImportExport\Components\Service\ImportService;
use SwagImportExport\Components\Service\Struct\PreparationResultStruct;
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

    public function __construct(
        UploadPathProvider $uploadPathProvider,
        ImportService $importService
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->importService = $importService;
    }

    public function initAcl(): void
    {
        $this->addAclPermission('prepareImport', 'import', 'Insuficient Permissions (prepareImport)');
        $this->addAclPermission('import', 'import', 'Insuficient Permissions (import)');
    }

    public function prepareImportAction(): void
    {
        $request = $this->Request();

        $postData = [
            'sessionId' => $request->getParam('sessionId'),
            'profileId' => (int) $request->getParam('profileId'),
            'type' => 'import',
            'file' => $this->uploadPathProvider->getRealPath($request->getParam('importFile')),
        ];

        if (empty($postData['file'])) {
            $this->View()->assign(['success' => false, 'msg' => 'No valid file']);

            return;
        }

        // get file format
        $inputFileName = $this->uploadPathProvider->getFileNameFromPath($postData['file']);
        $extension = $this->uploadPathProvider->getFileExtension($postData['file']);

        if (!$this->isFormatValid($extension)) {
            $this->View()->assign(['success' => false, 'msg' => 'No valid file format']);

            return;
        }

        $postData['format'] = $extension;

        try {
            /** @var PreparationResultStruct $resultStruct */
            $resultStruct = $this->importService->prepareImport($postData, $inputFileName);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);

            return;
        }

        $this->View()->assign([
            'success' => true,
            'position' => $resultStruct->getPosition(),
            'count' => $resultStruct->getTotalResultCount(),
        ]);
    }

    public function importAction(): void
    {
        $request = $this->Request();
        $inputFile = $this->uploadPathProvider->getRealPath($request->getParam('importFile'));

        $unprocessedFiles = [];
        $postData = [
            'type' => 'import',
            'profileId' => (int) $request->getParam('profileId'),
            'importFile' => $inputFile,
            'sessionId' => $request->getParam('sessionId'),
            'limit' => [],
            'format' => \pathinfo($inputFile, \PATHINFO_EXTENSION),
        ];

        if ($request->getParam('unprocessedFiles')) {
            $unprocessedFiles = \json_decode($request->getParam('unprocessedFiles'), true);
        }

        try {
            $resultData = $this->importService->import($postData, $unprocessedFiles, $inputFile);
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

    /**
     * Check is file format valid
     */
    private function isFormatValid(string $extension): bool
    {
        switch ($extension) {
            case 'csv':
            case 'xml':
                return true;
            default:
                return false;
        }
    }
}
