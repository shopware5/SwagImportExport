<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\CustomModels\ImportExport\Expression;
use Shopware\CustomModels\ImportExport\Profile;
use Shopware\CustomModels\ImportExport\Repository;

/**
 * Shopware ImportExport Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagImportExport
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
class Shopware_Controllers_Backend_SwagImportExportConversion extends Shopware_Controllers_Backend_ExtJs
{
    public function initAcl()
    {
        $this->addAclPermission("getConversions", "export", "Insuficient Permissions (getConversions)");
        $this->addAclPermission("createConversion", "export", "Insuficient Permissions (createConversion)");
        $this->addAclPermission("updateConversion", "export", "Insuficient Permissions (updateConversion)");
        $this->addAclPermission("deleteConversion", "export", "Insuficient Permissions (deleteConversion)");
    }

    public function getConversionsAction()
    {
        $profileId = $this->Request()->getParam('profileId');
        $filter = $this->Request()->getParam('filter', []);

        $manager = $this->getModelManager();
        /** @var Repository $expressionRepository */
        $expressionRepository = $manager->getRepository(Expression::class);

        $filter = array_merge(['p.id' => $profileId], $filter);

        $query = $expressionRepository->getExpressionsListQuery(
            $filter,
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit', null),
            $this->Request()->getParam('start')
        )->getQuery();

        $count = $manager->getQueryCount($query);

        $data = $query->getArrayResult();

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $count
        ]);
    }

    public function createConversionAction()
    {
        $profileId = $this->Request()->getParam('profileId');
        $data = $this->Request()->getParam('data', 1);

        $manager = $this->getModelManager();
        $profileRepository = $manager->getRepository(Profile::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $profileId]);

        $expressionEntity = new Expression();

        $expressionEntity->setProfile($profileEntity);
        $expressionEntity->setVariable($data['variable']);
        $expressionEntity->setExportConversion($data['exportConversion']);
        $expressionEntity->setImportConversion($data['importConversion']);

        $manager->persist($expressionEntity);
        $manager->flush();

        $this->View()->assign([
            'success' => true,
            'data' => [
                "id" => $expressionEntity->getId(),
                'profileId' => $expressionEntity->getProfile()->getId(),
                'exportConversion' => $expressionEntity->getExportConversion(),
                'importConversion' => $expressionEntity->getImportConversion(),
            ]
        ]);
    }

    public function updateConversionAction()
    {
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = [$data];
        }

        $manager = $this->getModelManager();
        /** @var Repository $expressionRepository */
        $expressionRepository = $manager->getRepository(Expression::class);

        try {
            foreach ($data as $expression) {
                /** @var Expression $expressionEntity */
                $expressionEntity = $expressionRepository->findOneBy(['id' => $expression['id']]);
                $expressionEntity->setVariable($expression['variable']);
                $expressionEntity->setExportConversion($expression['exportConversion']);
                $expressionEntity->setImportConversion($expression['importConversion']);
                $manager->persist($expressionEntity);
            }

            $manager->flush();

            $this->View()->assign(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'message' => $e->getMessage(), 'data' => $data]);
        }
    }

    public function deleteConversionAction()
    {
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = [$data];
        }

        $manager = $this->getModelManager();
        /** @var Repository $expressionRepository */
        $expressionRepository = $manager->getRepository(Expression::class);

        try {
            foreach ($data as $expression) {
                $expressionEntity = $expressionRepository->findOneBy(['id' => $expression['id']]);
                $manager->remove($expressionEntity);
            }

            $manager->flush();

            $this->View()->assign(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            $this->View()->assign(['success' => false, 'message' => $e->getMessage(), 'data' => $data]);
        }
    }
}
