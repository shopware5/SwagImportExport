<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\SwagImportExport\Service\ExportService;

/**
 * Shopware ImportExport Plugin
 *
 * Export Controller to handle all exports
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImportExport
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExportExport extends Shopware_Controllers_Backend_ExtJs
{
    public function initAcl()
    {
        $this->addAclPermission("prepareExport", "export", "Insuficient Permissions (prepareExport)");
        $this->addAclPermission("export", "export", "Insuficient Permissions (export)");
    }

    public function prepareExportAction()
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
            'filter' =>  [],
            'limit' =>  [
                'limit' => $limit,
                'offset' => $offset,
            ]
        ];

        /** @var ExportService $exportService */
        $exportService = $this->get('swag_import_export.export_service');

        try {
            $resultStruct = $exportService->prepareExport($postData, $this->Request()->getParams());
        } catch (\Exception $e) {
            return $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);
        }

        if ($resultStruct->getTotalResultCount() === 0) {
            return $this->View()->assign(['success' => false, 'msg' => 'No data to export', 'position' => 0, 'count' => 0]);
        }

        return $this->View()->assign([
            'success' => true,
            'position' => $resultStruct->getPosition(),
            'count' => $resultStruct->getTotalResultCount()
        ]);
    }

    public function exportAction()
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
            'filter' =>  [],
            'limit' =>  [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];

        /** @var ExportService $exportService */
        $exportService = $this->get('swag_import_export.export_service');

        try {
            $resultData = $exportService->export($postData, $this->Request()->getParams());
            $this->View()->assign(['success' => true, 'data' => $resultData]);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);
        }
    }
}
