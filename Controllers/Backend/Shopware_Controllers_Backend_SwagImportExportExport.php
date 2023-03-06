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
use SwagImportExport\Components\Service\ExportServiceInterface;
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
    private ExportServiceInterface $exportService;

    private ProfileFactory $profileFactory;

    private SessionService $sessionService;

    private UploadPathProvider $uploadPathProvider;

    private \Shopware_Components_Config $config;

    public function __construct(
        ExportServiceInterface $exportService,
        ProfileFactory $profileFactory,
        SessionService $sessionService,
        UploadPathProvider $uploadPathProvider,
        \Shopware_Components_Config $config
    ) {
        $this->exportService = $exportService;
        $this->profileFactory = $profileFactory;
        $this->sessionService = $sessionService;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->config = $config;
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

        $lastPosition = $session->getPosition();
        try {
            foreach ($this->exportService->export($exportRequest, $session) as $position) {
                $lastPosition = $position;
                break;
            }
        } catch (\Throwable $e) {
            $this->View()->assign(['success' => false, 'msg' => $e->getMessage()]);

            return;
        }

        $this->View()->assign([
            'success' => true,
            'data' => [
                'position' => $lastPosition,
                'fileName' => $fileName,
                'profileId' => $profile->getId(),
                'sessionId' => $session->getEntity()->getId(),
                'format' => $format,
            ],
        ]);
    }

    protected function initAcl(): void
    {
        $this->addAclPermission('prepareExport', 'export', 'Insufficient Permissions (prepareExport)');
        $this->addAclPermission('export', 'export', 'Insufficient Permissions (export)');
    }

    private function getExportRequest(Profile $profile, string $format): ExportRequest
    {
        $auth = $this->get('auth');
        $request = $this->Request();

        $exportRequest = new ExportRequest();
        $exportRequest->setData([
            'sessionId' => $request->getParam('sessionId') ? (int) $request->getParam('sessionId') : null,
            'profileEntity' => $profile,
            'format' => $format,
            'filter' => [],
            'limit' => $request->getParam('limit') ? (int) $request->getParam('limit') : null,
            'offset' => $request->getParam('offset') ? (int) $request->getParam('offset') : null,
            'username' => $auth->getIdentity()->name ?: 'Backend user',
            'category' => $request->getParam('categories') ? [$request->getParam('categories')] : null,
            'batchSize' => $this->config->getByNamespace('SwagImportExport', 'batch-size-export', 1),
            'productStream' => $request->getParam('productStreamId') ? [$request->getParam('productStreamId')] : null,
            'exportVariants' => $request->getParam('variants') ? (bool) $request->getParam('variants') : null,
            'stockFilter' => $request->getParam('stockFilter') ?: null,
            'customFilterDirection' => $request->getParam('customFilterDirection') ?: null,
            'customFilterValue' => $request->getParam('customFilterValue') ?: null,
            'ordernumberFrom' => $request->getParam('ordernumberFrom') ?: null,
            'dateFrom' => $request->getParam('dateFrom') ?: null,
            'dateTo' => $request->getParam('dateTo') ?: null,
            'orderstate' => $request->getParam('orderstate'),
            'paymentstate' => $request->getParam('paymentstate'),
            'customerStreamId' => $request->getParam('customerStreamId') ?: null,
            'customerId' => $request->getParam('customerId') ?: null,
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
