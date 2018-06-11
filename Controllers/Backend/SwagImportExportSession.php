<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Shopware\Components\SwagImportExport\Utils\DataHelper;
use Shopware\CustomModels\ImportExport\Repository;
use Shopware\CustomModels\ImportExport\Session;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 *
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExportSession extends Shopware_Controllers_Backend_ExtJs
{
    public function initAcl()
    {
        $this->addAclPermission('getSessions', 'read', 'Insuficient Permissions (getSessions)');
        $this->addAclPermission('deleteSession', 'export', 'Insuficient Permissions (deleteSession)');
    }

    public function getSessionDetailsAction()
    {
        $manager = $this->getModelManager();
        $sessionId = $this->Request()->getParam('sessionId');

        if (null === $sessionId) {
            $this->View()->assign(['success' => false, 'message' => 'No session found']);
        }
        /** @var Repository $sessionRepository */
        $sessionRepository = $manager->getRepository(Session::class);
        /** @var Session $sessionModel */
        $sessionModel = $sessionRepository->find($sessionId);

        if (empty($sessionModel)) {
            $this->View()->assign(['success' => false, 'message' => 'No session found']);
        }

        $dataSet = [
            'fileName' => $sessionModel->getFileName(),
            'type' => $sessionModel->getType(),
            'profile' => $sessionModel->getProfile()->getName(),
            'dataset' => $sessionModel->getTotalCount(),
            'position' => $sessionModel->getPosition(),
            'fileSize' => DataHelper::formatFileSize($sessionModel->getFileSize()),
            'userName' => $sessionModel->getUserName(),
            'date' => $sessionModel->getCreatedAt()->format('d.m.Y H:i'),
            'status' => $sessionModel->getState(),
        ];

        $result = $this->translateDataSet($dataSet);

        $this->View()->assign(['success' => true, 'data' => $result]);
    }

    public function getSessionsAction()
    {
        $manager = $this->getModelManager();
        /** @var Repository $sessionRepository */
        $sessionRepository = $manager->getRepository(Session::class);

        $query = $sessionRepository->getSessionsListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit', 25),
            $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        //returns the total count of the query
        $total = $paginator->count();

        //returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        foreach ($data as $key => $row) {
            $data[$key]['fileUrl'] = urlencode($row['fileName']);
            $data[$key]['fileName'] = $row['fileName'];
            $data[$key]['fileSize'] = DataHelper::formatFileSize($row['fileSize']);
        }

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $total,
        ]);
    }

    /**
     * Deletes a single order from the database.
     * Expects a single order id which placed in the parameter id
     */
    public function deleteSessionAction()
    {
        $manager = $this->getModelManager();
        try {
            $data = $this->Request()->getParam('data');

            if (is_array($data) && isset($data['id'])) {
                $data = [$data];
            }

            foreach ($data as $record) {
                $sessionId = $record['id'];

                if (empty($sessionId) || !is_numeric($sessionId)) {
                    $this->View()->assign([
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'No valid Id',
                    ]);

                    return;
                }

                /** @var Session $entity */
                $entity = $manager->getRepository(Session::class)->find($sessionId);
                $manager->remove($entity);
            }

            //Performs all of the collected actions.
            $manager->flush();

            $this->View()->assign([
                'success' => true,
                'data' => $this->Request()->getParams(),
            ]);
        } catch (Exception $e) {
            $this->View()->assign([
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function translateDataSet(array $data)
    {
        /** @var Shopware_Components_Snippet_Manager $snippetManager */
        $snippetManager = $this->get('snippets');
        $namespace = $snippetManager->getNamespace('backend/swag_import_export/session_data');
        $result = [];

        foreach ($data as $key => $value) {
            $result[$namespace->get($key, $key)] = $namespace->get($value, $value);
        }

        return $result;
    }
}
