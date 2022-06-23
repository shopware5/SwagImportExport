<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use SwagImportExport\Components\Service\ExportService;

/**
 * Shopware ImportExport Plugin
 *
 * Export Controller to handle all exports
 */
class Shopware_Controllers_Backend_SwagImportExportExport extends \Shopware_Controllers_Backend_ExtJs
{
    private ExportService $exportService;

    public function __construct(
        ExportService $exportService
    ) {
        $this->exportService = $exportService;
    }

    public function initAcl(): void
    {
        $this->addAclPermission('prepareExport', 'export', 'Insufficient Permissions (prepareExport)');
        $this->addAclPermission('export', 'export', 'Insufficient Permissions (export)');
    }

    public function prepareExportAction(): void
    {
        $limit = null;
        if ($this->Request()->getParam('limit')) {
            $limit = $this->Request()->getParam('limit');
        }

        $offset = null;
        if ($this->Request()->getParam('offset')) {
            $offset = $this->Request()->getParam('offset');
        }

        $postData = [
            'sessionId' => $this->Request()->getParam('sessionId'),
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format'),
            'filter' => [],
            'limit' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];

        try {
            $resultStruct = $this->exportService->prepareExport($postData, $this->Request()->getParams());
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);

            return;
        }

        if ($resultStruct->getTotalResultCount() === 0) {
            $this->View()->assign(['success' => false, 'msg' => 'No data to export', 'position' => 0, 'count' => 0]);

            return;
        }

        $this->View()->assign([
            'success' => true,
            'position' => $resultStruct->getPosition(),
            'count' => $resultStruct->getTotalResultCount(),
        ]);
    }

    public function exportAction(): void
    {
        $limit = null;
        if ($this->Request()->getParam('limit')) {
            $limit = $this->Request()->getParam('limit');
        }

        $offset = null;
        if ($this->Request()->getParam('offset')) {
            $offset = $this->Request()->getParam('offset');
        }

        $postData = [
            'profileId' => (int) $this->Request()->getParam('profileId'),
            'type' => 'export',
            'format' => $this->Request()->getParam('format'),
            'sessionId' => $this->Request()->getParam('sessionId'),
            'fileName' => $this->Request()->getParam('fileName'),
            'filter' => [],
            'limit' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];

        try {
            $resultData = $this->exportService->export($postData, $this->Request()->getParams());
            $this->View()->assign(['success' => true, 'data' => $resultData]);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
}
