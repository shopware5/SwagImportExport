<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagImportExport\Components\Service\ImportService;
use SwagImportExport\Components\Service\Struct\PreparationResultStruct;
use SwagImportExport\Components\UploadPathProvider;

/**
 * Shopware ImportExport Plugin
 *
 * Import controller to handle all imports.
 */
class Shopware_Controllers_Backend_SwagImportExportImport extends Shopware_Controllers_Backend_ExtJs
{
    public function initAcl(): void
    {
        $this->addAclPermission('prepareImport', 'import', 'Insuficient Permissions (prepareImport)');
        $this->addAclPermission('import', 'import', 'Insuficient Permissions (import)');
    }

    public function prepareImportAction(): void
    {
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');
        $request = $this->Request();

        $postData = [
            'sessionId' => $request->getParam('sessionId'),
            'profileId' => (int) $request->getParam('profileId'),
            'type' => 'import',
            'file' => $uploadPathProvider->getRealPath($request->getParam('importFile')),
        ];

        if (empty($postData['file'])) {
            $this->View()->assign(['success' => false, 'msg' => 'No valid file']);

            return;
        }

        // get file format
        $inputFileName = $uploadPathProvider->getFileNameFromPath($postData['file']);
        $extension = $uploadPathProvider->getFileExtension($postData['file']);

        if (!$this->isFormatValid($extension)) {
            $this->View()->assign(['success' => false, 'msg' => 'No valid file format']);

            return;
        }

        $postData['format'] = $extension;

        $importService = $this->get('swag_import_export.import_service');

        try {
            /** @var PreparationResultStruct $resultStruct */
            $resultStruct = $importService->prepareImport($postData, $inputFileName);
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
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');
        $request = $this->Request();
        $inputFile = $uploadPathProvider->getRealPath($request->getParam('importFile'));

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

        /** @var ImportService $importService */
        $importService = $this->get('swag_import_export.import_service');

        try {
            $resultData = $importService->import($postData, $unprocessedFiles, $inputFile);
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
