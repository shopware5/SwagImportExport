<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\DBAL\DBALException;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\SwagImportExport\Service\ProfileService;
use Shopware\Components\SwagImportExport\Utils\TreeHelper;
use Shopware\CustomModels\ImportExport\Profile;
use Shopware\CustomModels\ImportExport\Repository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Backend_SwagImportExportProfile extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /**
     * @var Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    protected $plugin;

    public function __construct()
    {
        parent::__construct();

        $this->plugin = Shopware()->Plugins()->Backend()->SwagImportExport();
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'exportProfile',
        ];
    }

    public function initAcl()
    {
        $this->addAclPermission('getProfiles', 'profile', 'Insuficient Permissions (getProfiles)');
        $this->addAclPermission('createProfiles', 'profile', 'Insuficient Permissions (createProfiles)');
        $this->addAclPermission('updateProfiles', 'profile', 'Insuficient Permissions (updateProfiles)');
        $this->addAclPermission('deleteProfiles', 'profile', 'Insuficient Permissions (deleteProfiles)');
        $this->addAclPermission('getProfile', 'profile', 'Insuficient Permissions (getProfile)');
        $this->addAclPermission('duplicateProfile', 'profile', 'Insuficient Permissions (duplicateProfile)');
        $this->addAclPermission('exportProfile', 'profile', 'Insuficient Permissions (exportProfile)');
        $this->addAclPermission('importProfile', 'profile', 'Insuficient Permissions (importProfile)');
        $this->addAclPermission('createNode', 'export', 'Insuficient Permissions (createNode)');
        $this->addAclPermission('updateNode', 'export', 'Insuficient Permissions (updateNode)');
        $this->addAclPermission('deleteNode', 'export', 'Insuficient Permissions (deleteNode)');
        $this->addAclPermission('getSections', 'profile', 'Insuficient Permissions (getSections)');
        $this->addAclPermission('getColumns', 'profile', 'Insuficient Permissions (getColumns)');
        $this->addAclPermission('getParentKeys', 'profile', 'Insuficient Permissions (getParentKeys)');
    }

    /**
     * Returns all profiles into an array
     */
    public function getProfilesAction()
    {
        $manager = $this->getModelManager();
        /** @var Repository $profileRepository */
        $profileRepository = $manager->getRepository(Profile::class);

        $query = $profileRepository->getProfilesListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit'),
            $this->Request()->getParam('start')
        )->getQuery();

        $count = $manager->getQueryCount($query);

        $data = $query->getArrayResult();
        /** @var Enlight_Components_Snippet_Namespace $namespace */
        $namespace = $this->get('snippets')->getNamespace('backend/swag_import_export/default_profiles');

        foreach ($data as &$profile) {
            if ($profile['default'] === true) {
                $profile['translation'] = $namespace->get($profile['name']);
                $profile['description'] = $namespace->get($profile['description']);
            }
        }
        unset($profile);

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $count,
        ]);
    }

    /**
     * @return Enlight_View|Enlight_View_Default
     */
    public function createProfilesAction()
    {
        $data = $this->Request()->getParam('data', 1);

        try {
            $profileEntity = $this->plugin->getProfileFactory()->createProfileModel($data);

            return $this->View()->assign([
                'success' => true,
                'data' => [
                    'id' => $profileEntity->getId(),
                    'name' => $profileEntity->getName(),
                    'type' => $profileEntity->getType(),
                    'baseProfile' => $profileEntity->getBaseProfile(),
                    'tree' => $profileEntity->getTree(),
                    'default' => $profileEntity->getDefault(),
                ],
            ]);
        } catch (DBALException $e) {
            return $this->View()->assign([
                'success' => false,
                'message' => $this->get('snippets')->getNamespace('backend/swag_import_export/controller')->get('swag_import_export/profile/duplicate_unique_error_msg'),
            ]);
        } catch (\Exception $e) {
            return $this->View()->assign(
                ['success' => false, 'msg' => $e->getMessage()]
            );
        }
    }

    /**
     * @return Enlight_View|Enlight_View_Default
     */
    public function deleteProfilesAction()
    {
        $manager = $this->getModelManager();
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = [$data];
        }

        try {
            $profileRepository = $manager->getRepository(Profile::class);
            foreach ($data as $profile) {
                $profileEntity = $profileRepository->findOneBy(['id' => $profile['id']]);
                $manager->remove($profileEntity);
            }
            $manager->flush();
        } catch (\Exception $e) {
            return $this->View()->assign([
                'success' => false,
            ]);
        }

        return $this->View()->assign(['success' => true]);
    }

    /**
     * @throws Exception
     *
     * @return Enlight_View|Enlight_View_Default
     */
    public function updateProfilesAction()
    {
        $manager = $this->getModelManager();
        $data = $this->Request()->getParam('data', 1);

        $profileRepository = $manager->getRepository(Profile::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $data['id']]);

        if (!$profileEntity) {
            throw new \Exception('Profile not found!');
        }

        $profileEntity->setName($data['name']);

        $manager->persist($profileEntity);
        try {
            $manager->flush();
        } catch (DBALException $e) {
            return $this->View()->assign([
                'success' => false,
                'message' => $this->get('snippets')->getNamespace('backend/swag_import_export/controller')->get('swag_import_export/profile/duplicate_unique_error_msg'),
            ]);
        } catch (\Exception $e) {
            return $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->View()->assign([
            'success' => true,
            'data' => [
                'id' => $profileEntity->getId(),
                'name' => $profileEntity->getName(),
                'type' => $profileEntity->getType(),
                'baseProfile' => $profileEntity->getBaseProfile(),
                'tree' => $profileEntity->getTree(),
                'default' => $profileEntity->getDefault(),
            ],
        ]);
    }

    /**
     * @return Enlight_View|Enlight_View_Default
     */
    public function getProfileAction()
    {
        $manager = $this->getModelManager();
        $profileId = $this->Request()->getParam('profileId', -1);

        if ($profileId === -1) {
            return $this->View()->assign(['success' => false, 'children' => []]);
        }

        $profileRepository = $manager->getRepository(Profile::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $profileId]);

        $tree = $profileEntity->getTree();
        $root = TreeHelper::convertToExtJSTree(json_decode($tree, 1));

        return $this->View()->assign(['success' => true, 'children' => $root]);
    }

    /**
     * @throws Exception
     *
     * @return Enlight_View|Enlight_View_Default
     */
    public function duplicateProfileAction()
    {
        $manager = $this->getModelManager();
        $snippetManager = $this->get('snippets');
        $profileId = (int) $this->Request()->getParam('profileId');

        $loadedProfile = $manager->find(Profile::class, $profileId);

        if (!$loadedProfile) {
            throw new \Exception(sprintf('Profile with id %s does NOT exist', $profileId));
        }

        $profile = new Profile();

        $profile->setBaseProfile($loadedProfile->getId());
        $profile->setName($loadedProfile->getName() . ' (' . $snippetManager->getNamespace('backend/swag_import_export/controller')->get('swag_import_export/profile/copy') . ')');
        $profile->setType($loadedProfile->getType());
        $profile->setTree($loadedProfile->getTree());

        $manager->persist($profile);
        try {
            $manager->flush();
        } catch (DBALException $e) {
            return $this->View()->assign([
                'success' => false,
                'message' => $snippetManager->getNamespace('backend/swag_import_export/controller')->get('swag_import_export/profile/duplicate_unique_error_msg'),
            ]);
        } catch (\Exception $e) {
            return $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->View()->assign([
            'success' => true,
            'data' => [
                'id' => $profile->getId(),
                'name' => $profile->getName(),
                'type' => $profile->getType(),
                'baseProfile' => $profile->getBaseProfile(),
                'tree' => $profile->getTree(),
                'default' => $profile->getDefault(),
            ],
        ]);
    }

    public function exportProfileAction()
    {
        $profileId = $this->Request()->get('profileId', null);

        if (empty($profileId)) {
            $this->View()->assign([
                'success' => false,
            ]);

            return;
        }

        /** @var ProfileService $service */
        $service = $this->get('swag_import_export.profile_service');

        try {
            $exportDataStruct = $service->exportProfile($profileId);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
            ]);

            return;
        }
        $this->Front()->Plugins()->Json()->setRenderer(false);

        $profileName = urlencode($exportDataStruct->getName());

        $response = $this->Response();
        $response->setHeader('Cache-Control', 'public');
        $response->setHeader('Content-Description', 'File Transfer');
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $profileName . '.json');
        $response->setHeader('Content-type', 'application/json');

        echo $exportDataStruct->getExportData();
    }

    /**
     * @return Enlight_View|Enlight_View_Default
     */
    public function importProfileAction()
    {
        /** @var UploadedFile $file */
        $file = Symfony\Component\HttpFoundation\Request::createFromGlobals()->files->get('profilefile');

        /** @var ProfileService $service */
        $service = $this->get('swag_import_export.profile_service');

        try {
            $service->importProfile($file);
        } catch (\Exception $e) {
            return $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        return $this->View()->assign([
            'success' => true,
        ]);
    }

    public function createNodeAction()
    {
        $manager = $this->getModelManager();
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileRepository = $manager->getRepository(Profile::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $profileId]);

        $tree = json_decode($profileEntity->getTree(), 1);

        if (isset($data['parentId'])) {
            $data = [$data];
        }

        $errors = false;

        foreach ($data as &$node) {
            $node['id'] = uniqid('', true);
            if (!TreeHelper::appendNode($node, $tree)) {
                $errors = true;
            }
        }
        unset($node);

        $profileEntity->setTree(json_encode($tree));

        $manager->persist($profileEntity);
        $manager->flush();

        if ($errors) {
            $this->View()->assign(['success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data]);
        } else {
            $this->View()->assign(['success' => true, 'children' => $data]);
        }
    }

    public function updateNodeAction()
    {
        $manager = $this->getModelManager();
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileRepository = $manager->getRepository(Profile::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $profileId]);
        $profileType = $profileEntity->getType();
        $defaultFields = [];

        $dataManager = $this->plugin->getDataFactory()->createDataManager($profileType);
        if ($dataManager) {
            $defaultFields = $dataManager->getDefaultFields();
        }

        $tree = json_decode($profileEntity->getTree(), 1);

        if (isset($data['parentId'])) {
            $data = [$data];
        }

        $errors = false;

        foreach ($data as &$node) {
            if (!TreeHelper::changeNode($node, $tree, $defaultFields)) {
                $errors = true;
                break;
            }

            // the root cannot be moved or deleted
            if ($node['id'] === 'root') {
                continue;
            }

            $changedNode = TreeHelper::getNodeById($node['id'], $tree);

            if ($node['parentId'] != $changedNode['parentId']) {
                $changedNode['parentId'] = $node['parentId'];
                $changedNode['index'] = $node['index'];
                $changedNode['type'] = $node['type'];
                if (!TreeHelper::deleteNode($node, $tree)) {
                    $errors = true;
                    break;
                }

                if (!TreeHelper::moveNode($changedNode, $tree)) {
                    $errors = true;
                    break;
                }
            }
        }
        unset($node);
        $reorderedTree = TreeHelper::reorderTree($tree);

        $profileEntity->setTree(json_encode($reorderedTree));

        $manager->persist($profileEntity);
        $manager->flush();

        if ($errors) {
            $this->View()->assign(['success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data]);
        } else {
            $this->View()->assign(['success' => true, 'children' => $data]);
        }
    }

    public function deleteNodeAction()
    {
        $manager = $this->getModelManager();
        $profileId = $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileRepository = $manager->getRepository(Profile::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $profileId]);

        $tree = json_decode($profileEntity->getTree(), true);

        if (isset($data['parentId'])) {
            $data = [$data];
        }

        $errors = false;

        foreach ($data as &$node) {
            if (!TreeHelper::deleteNode($node, $tree)) {
                $errors = true;
            }
        }
        unset($node);

        $profileEntity->setTree(json_encode($tree));

        $manager->persist($profileEntity);
        $manager->flush();

        if ($errors) {
            $this->View()->assign(['success' => false, 'message' => 'Some of the nodes could not be saved', 'children' => $data]);
        } else {
            $this->View()->assign(['success' => true, 'children' => $data]);
        }
    }

    public function getSectionsAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');

        if (!$postData['profileId']) {
            $this->View()->assign([
                'success' => false, 'message' => 'No profile Id',
            ]);

            return;
        }

        $profile = $this->plugin->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->plugin->getDataFactory()->createDbAdapter($type);

        $sections = $dbAdapter->getSections();

        $this->View()->assign([
            'success' => true,
            'data' => $sections,
            'total' => count($sections),
        ]);
    }

    public function getColumnsAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');
        $section = $this->Request()->getParam('adapter', 'default');

        if (!$postData['profileId']) {
            $this->View()->assign([
                'success' => false, 'message' => 'No profile Id',
            ]);

            return;
        }

        $profile = $this->plugin->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->plugin->getDataFactory()->createDbAdapter($type);
        $dataManager = $this->plugin->getDataFactory()->createDataManager($type);

        $defaultFieldsName = null;
        $defaultFields = [];
        if ($dataManager) {
            $defaultFields = $dataManager->getDefaultFields();
            $defaultFieldsName = $dataManager->getDefaultFieldsName();
        }

        $columns = $dbAdapter->getColumns($section);

        if (!$columns || empty($columns)) {
            $this->View()->assign([
                'success' => false, 'msg' => 'No columns found.',
            ]);
        }

        // merge all sections
        if ($section === 'default' && count($dbAdapter->getSections()) > 1) {
            $columns = array_reduce($columns, static function ($carry, $item) {
                return array_merge($carry, $item);
            }, []);
        }

        foreach ($columns as &$column) {
            $match = '';
            preg_match('/(?<=as ).*/', $column, $match);

            $match = trim($match[0]);

            if ($match !== '') {
                $column = $match;
            } else {
                preg_match('/(?<=\.).*/', $column, $match);
                $match = trim($match[0]);
                if ($match !== '') {
                    $column = $match;
                }
            }

            $column = ['id' => $column, 'name' => $column];

            if ($defaultFieldsName && in_array($column['name'], $defaultFieldsName, true)) {
                $column['default'] = true;
                $column['type'] = $dataManager->getFieldType($column['name'], $defaultFields);
            }
        }
        unset($column);

        $this->View()->assign([
            'success' => true, 'data' => $columns, 'total' => count($columns),
        ]);
    }

    public function getParentKeysAction()
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');
        $section = $this->Request()->getParam('adapter', 'default');

        if (!$postData['profileId']) {
            $this->View()->assign([
                'success' => false, 'message' => 'No profile Id',
            ]);

            return;
        }

        $profile = $this->plugin->getProfileFactory()->loadProfile($postData);
        $type = $profile->getType();

        $dbAdapter = $this->plugin->getDataFactory()->createDbAdapter($type);

        if (!method_exists($dbAdapter, 'getParentKeys')) {
            $this->View()->assign([
                'success' => true, 'data' => [], 'total' => 0,
            ]);

            return;
        }

        $columns = $dbAdapter->getParentKeys($section);

        foreach ($columns as &$column) {
            $match = '';
            preg_match('/(?<=as ).*/', $column, $match);

            $match = trim($match[0]);

            if ($match !== '') {
                $column = $match;
            } else {
                preg_match('/(?<=\.).*/', $column, $match);
                $match = trim($match[0]);
                if ($match !== '') {
                    $column = $match;
                }
            }

            $column = ['id' => $column, 'name' => $column];
        }
        unset($column);

        $this->View()->assign([
            'success' => true, 'data' => $columns, 'total' => count($columns),
        ]);
    }
}
