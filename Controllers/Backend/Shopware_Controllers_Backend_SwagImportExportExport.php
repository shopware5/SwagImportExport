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
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Service\ExportService;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ExportRequest;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\FileNameGenerator;

/**
 * Shopware ImportExport Plugin
 *
 * Export Controller to handle all exports
 */
class Shopware_Controllers_Backend_SwagImportExportExport extends \Shopware_Controllers_Backend_ExtJs
{
    private ExportService $exportService;

    private ProfileFactory $profileFactory;

    private SessionService $sessionService;

    private UploadPathProvider $uploadPathProvider;

    private \Shopware_Components_Config $config;

    private \Shopware_Components_Auth $auth;

    public function __construct(
        ExportService $exportService,
        ProfileFactory $profileFactory,
        SessionService $sessionService,
        UploadPathProvider $uploadPathProvider,
        \Shopware_Components_Config $config,
        \Shopware_Components_Auth $auth
    ) {
        $this->exportService = $exportService;
        $this->profileFactory = $profileFactory;
        $this->sessionService = $sessionService;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->config = $config;
        $this->auth = $auth;
    }

    public function initAcl(): void
    {
        $this->addAclPermission('prepareExport', 'export', 'Insufficient Permissions (prepareExport)');
        $this->addAclPermission('export', 'export', 'Insufficient Permissions (export)');
    }

    public function prepareExportAction(): void
    {
        $profile = $this->getProfile();
        $format = $this->getFormat();
        $exportRequest = $this->getExportRequest($profile, $format);

        $session = $this->sessionService->loadSession($exportRequest->sessionId);

        try {
            $count = $this->exportService->prepareExport($exportRequest, $session);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);

            return;
        }

        if ($count === 0) {
            $this->View()->assign(['success' => false, 'msg' => 'No data to export', 'position' => 0, 'count' => 0]);

            return;
        }

        $this->View()->assign([
            'success' => true,
            'position' => 0,
            'count' => $count,
        ]);
    }

    public function exportAction(): void
    {
        $format = $this->getFormat();
        $profile = $this->getProfile();

        $fileName = $this->Request()->getParam('fileName');
        if (!$fileName) {
            $fileName = FileNameGenerator::generateFileName('export', $format, $profile->getEntity());
        }

        $exportRequest = $this->getExportRequest($profile, $format);
        $exportRequest->filePath = $this->uploadPathProvider->getRealPath($fileName);

        $session = $this->sessionService->loadSession($exportRequest->sessionId);

        $lastPosition = 0;
        try {
            foreach ($this->exportService->export($exportRequest, $session) as $position) {
                $lastPosition = $position;
            }
        } catch (\Throwable $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);

            return;
        }

        $this->View()->assign(['success' => true, 'data' => [
            'position' => $lastPosition,
            'fileName' => $fileName,
            'profileId' => $this->Request()->getParam('profileId'),
        ]]);
    }

    private function getExportRequest(Profile $profile, string $format): ExportRequest
    {
        $exportRequest = new ExportRequest();
        $exportRequest->setData([
            'sessionId' => $this->Request()->getParam('sessionId') ? (int) $this->Request()->getParam('sessionId') : null,
            'profileEntity' => $profile,
            'type' => 'export',
            'format' => $format,
            'filter' => [],
            'limit' => $this->Request()->getParam('limit') ? (int) $this->Request()->getParam('limit') : null,
            'offset' => $this->Request()->getParam('offset') ? (int) $this->Request()->getParam('offset') : null,
            'username' => $this->auth->getIdentity()->name ?: 'Cli',
            'category' => $this->Request()->getParam('categories') ? [$this->Request()->getParam('categories')] : null,
            'batchSize' => $this->config->getByNamespace('SwagImportExport', 'batch-size-export', 1),
            'productStream' => $this->Request()->getParam('productStreamId') ? [$this->Request()->getParam('productStreamId')] : null,
            'exportVariants' => $this->Request()->getParam('variants') ? (bool) $this->Request()->getParam('variants') : null,
            'stockFilter' => $this->Request()->getParam('stockFilter') ?: null,
            'customFilterDirection' => $this->Request()->getParam('customFilterDirection') ?: null,
            'customFilterValue' => $this->Request()->getParam('customFilterValue') ?: null,
            'ordernumberFrom' => $this->Request()->getParam('ordernumberFrom') ?: null,
            'dateFrom' => $this->Request()->getParam('dateFrom') ?: null,
            'dateTo' => $this->Request()->getParam('dateTo') ?: null,
            'orderstate' => $this->Request()->getParam('orderstate') ?: null,
            'paymentstate' => $this->Request()->getParam('paymentstate') ?: null,
            'customerStreamId' => $this->Request()->getParam('customerStreamId') ?: null,
            'customerId' => $this->Request()->getParam('customerId') ?: null,
        ]);

        return $exportRequest;
    }

    private function getProfile(): Profile
    {
        $profileId = $this->Request()->getParam('profileId');
        if (!$profileId) {
            throw new \UnexpectedValueException('ProfileId must be set');
        }

        return $this->profileFactory->loadProfile((int) $profileId);
    }

    private function getFormat(): string
    {
        $format = $this->Request()->getParam('format');
        if (!$format) {
            throw new \UnexpectedValueException('Format must be set');
        }

        return $format;
    }
}
