<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\CSRFWhitelistAware;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Models\Logger;
use SwagImportExport\Models\LoggerRepository;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Backend_SwagImportExport extends \Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private UploadPathProvider $uploadPathProvider;

    public function __construct(UploadPathProvider $uploadPathProvider)
    {
        $this->uploadPathProvider = $uploadPathProvider;
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions(): array
    {
        return [
            'downloadFile',
        ];
    }

    public function uploadFileAction(): void
    {
        $fileBag = new FileBag($_FILES);

        $clientOriginalName = '';
        foreach ($fileBag->getIterator() as $file) {
            $clientOriginalName = $file->getClientOriginalName();
            $file->move($this->uploadPathProvider->getPath(), $clientOriginalName);
        }

        $this->view->assign([
            'success' => true,
            'data' => [
                'path' => $this->uploadPathProvider->getRealPath($clientOriginalName),
                'fileName' => $clientOriginalName,
            ],
        ]);
    }

    /**
     * Fires when the user want to open a generated order document from the backend order module.
     *
     * Returns the created pdf file with an echo.
     */
    public function downloadFileAction(): void
    {
        try {
            $fileName = $this->Request()->getParam('fileName');

            if ($fileName === null) {
                throw new \InvalidArgumentException('File name must be provided');
            }

            $filePath = $this->uploadPathProvider->getRealPath($fileName);

            $extension = $this->uploadPathProvider->getFileExtension($fileName);
            switch ($extension) {
                case 'csv':
                    $application = 'text/csv';
                    break;
                case 'xml':
                    $application = 'application/xml';
                    break;
                default:
                    throw new \UnexpectedValueException('File extension is not valid');
            }

            if (\file_exists($filePath)) {
                $this->View()->assign(
                    [
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'File not exist',
                    ]
                );
            }

            $this->Front()->Plugins()->ViewRenderer()->setNoRender();
            $this->Front()->Plugins()->Json()->setRenderer(false);

            $response = $this->Response();
            $response->clearHeaders();
            $response->headers->replace();

            $response->setHeader('Cache-Control', 'public');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename=' . $fileName);
            $response->setHeader('Content-Type', $application);
            $response->sendHeaders();

            \readfile($filePath);
            exit;
        } catch (\Exception $e) {
            $this->View()->assign(
                [
                    'success' => false,
                    'data' => $this->Request()->getParams(),
                    'message' => $e->getMessage(),
                ]
            );

            return;
        }
    }

    public function getLogsAction(): void
    {
        $modelManager = $this->getModelManager();
        /** @var LoggerRepository $repo */
        $repo = $modelManager->getRepository(Logger::class);
        $query = $repo->getLogListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            (int) $this->Request()->getParam('limit', 25),
            (int) $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $modelManager->createPaginator($query);

        // returns the total count of the query
        $total = $paginator->count();

        // returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $total,
        ]);
    }

    /**
     * Registers acl permissions for controller actions
     */
    public function initAcl(): void
    {
        $this->addAclPermission('uploadFile', 'import', 'Insufficient Permissions (uploadFile)');
        $this->addAclPermission('downloadFile', 'export', 'Insufficient Permissions (downloadFile)');
    }
}
