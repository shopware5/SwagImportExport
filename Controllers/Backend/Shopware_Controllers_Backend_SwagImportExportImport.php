<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Service\ImportServiceInterface;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Components\UploadPathProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shopware ImportExport Plugin
 *
 * Import controller to handle all imports.
 */
class Shopware_Controllers_Backend_SwagImportExportImport extends \Shopware_Controllers_Backend_ExtJs
{
    private UploadPathProvider $uploadPathProvider;

    private ImportServiceInterface $importService;

    private ProfileFactory $profileFactory;

    private SessionService $sessionService;

    public function __construct(
        UploadPathProvider $uploadPathProvider,
        ImportServiceInterface $importService,
        ProfileFactory $profileFactory,
        SessionService $sessionService
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->importService = $importService;
        $this->profileFactory = $profileFactory;
        $this->sessionService = $sessionService;
    }

    public function prepareImportAction(Request $request): void
    {
        if (!$request->get('profileId')) {
            $this->View()->assign(['success' => false, 'msg' => 'Request parameter "profileId" must be set']);

            return;
        }

        if (!$request->get('importFile')) {
            $this->View()->assign(['success' => false, 'msg' => 'Request parameter "importFile" must be set']);

            return;
        }

        try {
            $totalCount = $this->importService->prepareImport($this->getImportRequest($request));
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

    public function importAction(Request $request): void
    {
        $importRequest = $this->getImportRequest($request);

        $session = $this->sessionService->loadSession($importRequest->sessionId);

        try {
            $lastPosition = $session->getPosition();
            foreach ($this->importService->import($importRequest, $session) as [$profileName, $position]) {
                $lastPosition = $position;
                break;
            }

            if ($session->getState() === Session::SESSION_CLOSE) {
                if (str_ends_with($importRequest->inputFile, ImportServiceInterface::UNPROCESSED_DATA_FILE_ENDING)) {
                    unlink($importRequest->inputFile);
                }
                $unprocessedData = $this->importService->prepareImportOfUnprocessedData($importRequest);
                if (\is_array($unprocessedData)) {
                    $this->View()->assign([
                        'success' => true,
                        'data' => $unprocessedData,
                    ]);

                    return;
                }
            }

            $this->View()->assign([
                'success' => true,
                'data' => [
                    'importFile' => $request->get('importFile'),
                    'position' => $lastPosition,
                    'profileId' => $importRequest->profileEntity->getId(),
                    'sessionId' => $session->getEntity()->getId(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    protected function initAcl(): void
    {
        $this->addAclPermission('prepareImport', 'import', 'Insufficient Permissions (prepareImport)');
        $this->addAclPermission('import', 'import', 'Insufficient Permissions (import)');
    }

    private function getImportRequest(Request $request): ImportRequest
    {
        $config = $this->get('config');
        $inputFile = $this->uploadPathProvider->getRealPath($request->get('importFile'));
        $profile = $this->profileFactory->loadProfile((int) $request->get('profileId'));

        $auth = $this->get('auth');

        $importRequest = new ImportRequest();
        $importRequest->setData([
            'sessionId' => $request->get('sessionId') ? (int) $request->get('sessionId') : null,
            'profileEntity' => $profile,
            'inputFile' => $inputFile,
            'format' => $this->uploadPathProvider->getFileExtension($inputFile),
            'username' => $auth->getIdentity()->name ?: 'Backend user',
            'batchSize' => $profile->getType() === DataDbAdapter::PRODUCT_IMAGE_ADAPTER ? 1 : (int) $config->getByNamespace('SwagImportExport', 'batch-size-import', 50),
        ]);

        return $importRequest;
    }
}
