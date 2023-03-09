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
use Doctrine\ORM\Query;
use Shopware\Components\CSRFWhitelistAware;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\FileNameGenerator;
use SwagImportExport\Models\Logger;
use SwagImportExport\Models\LoggerRepository;
use SwagImportExport\Models\Profile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        $file = $this->Request()->files->get('fileId');
        if (!$file instanceof UploadedFile) {
            $this->View()->assign(['success' => false, 'message' => 'Uploaded file is not valid']);

            return;
        }

        if (!$file->isValid()) {
            $this->View()->assign(['success' => false, 'message' => $file->getErrorMessage()]);

            return;
        }

        $extension = $file->getClientOriginalExtension();
        if (!$this->isFormatValid($extension)) {
            $this->View()->assign(['success' => false, 'error' => 'invalidFileFormat', 'message' => 'No valid file format. Please use xml or csv.']);

            return;
        }

        $fileName = $this->createTimestampedRandomizedFileName($file->getClientOriginalName());

        $file->move($this->uploadPathProvider->getPath(), $fileName);

        $this->view->assign([
            'success' => true,
            'data' => [
                'path' => $this->uploadPathProvider->getRealPath($fileName),
                'fileName' => $fileName,
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
        /** @var Query<array<string, mixed>> $query */
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

        $this->View()->assign([
            'success' => true, 'data' => iterator_to_array($paginator), 'total' => $total,
        ]);
    }

    /**
     * Registers acl permissions for controller actions
     */
    protected function initAcl(): void
    {
        $this->addAclPermission('uploadFile', 'import', 'Insufficient Permissions (uploadFile)');
        $this->addAclPermission('downloadFile', 'export', 'Insufficient Permissions (downloadFile)');
    }

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

    private function createTimestampedRandomizedFileName(string $oldFileName): string
    {
        $fileName = pathinfo($oldFileName, \PATHINFO_FILENAME);
        $fileExtension = pathinfo($oldFileName, \PATHINFO_EXTENSION);

        // Little "hack", so the FileNameGenerator could be re-used
        $profile = new Profile();
        $profile->setType($fileName);

        return FileNameGenerator::generateFileName('import', $fileExtension, $profile);
    }
}
