<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\SwagVersionHelper;
use Shopware\CustomModels\ImportExport\Logger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExport extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'downloadFile',
        ];
    }

    public function indexAction()
    {
        parent::indexAction();
        $this->View()->assign('shopware53Installed', SwagVersionHelper::hasMinimumVersion('5.3.0'));
    }

    public function uploadFileAction()
    {
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');
        $fileBag = new FileBag($_FILES);

        $clientOriginalName = '';
        /** @var UploadedFile $file */
        foreach ($fileBag->getIterator() as $file) {
            $clientOriginalName = $file->getClientOriginalName();
            $file->move($uploadPathProvider->getPath(), $clientOriginalName);
        }

        $this->view->assign([
            'success' => true,
            'data' => [
                'path' => $uploadPathProvider->getRealPath($clientOriginalName),
                'fileName' => $clientOriginalName,
            ],
        ]);
    }

    /**
     * Fires when the user want to open a generated order document from the backend order module.
     *
     * Returns the created pdf file with an echo.
     */
    public function downloadFileAction()
    {
        /** @var UploadPathProvider $uploadPathProvider */
        $uploadPathProvider = $this->get('swag_import_export.upload_path_provider');

        try {
            $fileName = $this->Request()->getParam('fileName', null);

            if ($fileName === null) {
                throw new \Exception('File name must be provided');
            }

            $filePath = $uploadPathProvider->getRealPath($fileName);

            $extension = $uploadPathProvider->getFileExtension($fileName);
            switch ($extension) {
                case 'csv':
                    $application = 'text/csv';
                    break;
                case 'xml':
                    $application = 'application/xml';
                    break;
                default:
                    throw new \Exception('File extension is not valid');
            }

            if (file_exists($filePath)) {
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
            $response->clearRawHeaders();

            $response->setHeader('Cache-Control', 'public');
            $response->setHeader('Content-Description', 'File Transfer');
            $response->setHeader('Content-disposition', 'attachment; filename=' . $fileName);
            $response->setHeader('Content-Type', $application);
            $response->sendHeaders();

            readfile($filePath);
            exit();
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

    public function getLogsAction()
    {
        /** @var \Shopware\CustomModels\ImportExport\Repository $loggerRepository */
        $loggerRepository = $this->getModelManager()->getRepository(Logger::class);

        $query = $loggerRepository->getLogListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit', 25),
            $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $this->getModelManager()->createPaginator($query);

        //returns the total count of the query
        $total = $paginator->count();

        //returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $total,
        ]);
    }

    /**
     * Registers acl permissions for controller actions
     */
    public function initAcl()
    {
        $this->addAclPermission('uploadFile', 'import', 'Insuficient Permissions (uploadFile)');
        $this->addAclPermission('downloadFile', 'export', 'Insuficient Permissions (downloadFile)');
    }
}
