<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\ORM\AbstractQuery;
use Shopware\Components\SwagImportExport\Utils\DataHelper;
use Shopware\CustomModels\ImportExport\Session;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Backend_SwagImportExportSession extends Shopware_Controllers_Backend_ExtJs
{
    public function initAcl()
    {
        $this->addAclPermission('getSessions', 'read', 'Insuficient Permissions (getSessions)');
        $this->addAclPermission('deleteSession', 'export', 'Insuficient Permissions (deleteSession)');
    }

    /**
     * @return void
     */
    public function getSessionDetailsAction()
    {
        $manager = $this->getModelManager();
        $sessionId = $this->Request()->getParam('sessionId');

        if ($sessionId === null) {
            $this->View()->assign(['success' => false, 'message' => 'No session found']);

            return;
        }
        $sessionRepository = $manager->getRepository(Session::class);
        $sessionModel = $sessionRepository->find($sessionId);

        if (empty($sessionModel)) {
            $this->View()->assign(['success' => false, 'message' => 'No session found']);

            return;
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
        $manager = $this->get('models');
        $query = $manager->getRepository(Session::class)->getSessionsListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit', 25),
            $this->Request()->getParam('start', 0)
        )->getQuery();

        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $paginator = $manager->createPaginator($query);

        //returns the total count of the query
        $total = $paginator->count();

        //returns the customer data
        $data = $paginator->getIterator()->getArrayCopy();

        foreach ($data as $key => $row) {
            $data[$key]['fileUrl'] = \urlencode($row['fileName']);
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

            if (\is_array($data) && isset($data['id'])) {
                $data = [$data];
            }

            foreach ($data as $record) {
                $sessionId = $record['id'];

                if (empty($sessionId) || !\is_numeric($sessionId)) {
                    $this->View()->assign([
                        'success' => false,
                        'data' => $this->Request()->getParams(),
                        'message' => 'No valid Id',
                    ]);

                    return;
                }

                $entity = $manager->getRepository(Session::class)->find($sessionId);
                if ($entity instanceof Session) {
                    $manager->remove($entity);
                }
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
     * @param array<string, string|int|null> $data
     *
     * @return array<string, mixed>
     */
    private function translateDataSet(array $data): array
    {
        $namespace = $this->get('snippets')->getNamespace('backend/swag_import_export/session_data');
        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $namespace->get($key, $key)] = $namespace->get($value, $value);
        }

        return $result;
    }
}
