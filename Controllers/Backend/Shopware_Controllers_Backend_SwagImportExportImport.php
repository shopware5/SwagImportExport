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
use SwagImportExport\Components\Service\ImportServiceInterface;
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

    private \Shopware_Components_Auth $auth;

    public function __construct(
        UploadPathProvider $uploadPathProvider,
        ImportServiceInterface $importService,
        ProfileFactory $profileFactory,
        SessionService $sessionService,
        \Shopware_Components_Auth $auth
    ) {
        $this->uploadPathProvider = $uploadPathProvider;
        $this->importService = $importService;
        $this->profileFactory = $profileFactory;
        $this->sessionService = $sessionService;
        $this->auth = $auth;
    }

    public function prepareImportAction(Request $request): void
    {
        if (!$request->get('profileId')) {
            throw new \UnexpectedValueException('ProfileId must be set');
        }

        if (!$request->get('importFile')) {
            throw new \UnexpectedValueException('importFile must be set');
        }

        $importRequest = $this->getImportRequest($request);

        if (empty($importRequest->inputFile)) {
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

    public function importAction(Request $request): void
    {
        $importRequest = $this->getImportRequest($request);

        $session = $this->sessionService->createSession();

        try {
            $lastPosition = 0;
            foreach ($this->importService->import($importRequest, $session) as [$profileName, $position]) {
                if ($profileName === $importRequest->profileEntity->getName()) {
                    $lastPosition = $position;
                }
            }
            $resultData = [
                'importFile' => $importRequest->inputFile,
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

        $importRequest = new ImportRequest();
        $importRequest->setData([
            'sessionId' => $request->get('sessionId') ? (int) $request->get('sessionId') : null,
            'profileEntity' => $profile,
            'type' => 'import',
            'inputFile' => $inputFile,
            'format' => $this->uploadPathProvider->getFileExtension($inputFile),
            'username' => $this->auth->getIdentity()->name ?: 'Cli',
            'batchSize' => $profile->getType() === 'articlesImages' ? 1 : (int) $config->getByNamespace('SwagImportExport', 'batch-size-import', 1000),
        ]);

        return $importRequest;
    }
}
