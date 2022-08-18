<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use Doctrine\DBAL\DBALException;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Model\Exception\ModelNotFoundException;
use SwagImportExport\Components\DataManagers\DataManager;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Components\Service\ProfileServiceInterface;
use SwagImportExport\Components\Utils\TreeHelper;
use SwagImportExport\Models\Profile;
use SwagImportExport\Models\ProfileRepository;
use Symfony\Component\HttpFoundation\Request;

class Shopware_Controllers_Backend_SwagImportExportProfile extends \Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private DataProvider $dataProvider;

    private ProfileFactory $profileFactory;

    private \Shopware_Components_Snippet_Manager $snippetManager;

    private ProfileRepository $profileRepository;

    private ProfileServiceInterface $profileService;

    public function __construct(
        \Shopware_Components_Snippet_Manager $snippetManager,
        DataProvider $dataProvider,
        ProfileFactory $profileFactory,
        ProfileRepository $profileRepository,
        ProfileServiceInterface $profileService
    ) {
        $this->snippetManager = $snippetManager;
        $this->dataProvider = $dataProvider;
        $this->profileFactory = $profileFactory;
        $this->profileRepository = $profileRepository;
        $this->profileService = $profileService;
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions(): array
    {
        return [
            'exportProfile',
        ];
    }

    public function initAcl(): void
    {
        $this->addAclPermission('getProfiles', 'profile', 'Insufficient Permissions (getProfiles)');
        $this->addAclPermission('createProfiles', 'profile', 'Insufficient Permissions (createProfiles)');
        $this->addAclPermission('updateProfiles', 'profile', 'Insufficient Permissions (updateProfiles)');
        $this->addAclPermission('deleteProfiles', 'profile', 'Insufficient Permissions (deleteProfiles)');
        $this->addAclPermission('getProfile', 'profile', 'Insufficient Permissions (getProfile)');
        $this->addAclPermission('duplicateProfile', 'profile', 'Insufficient Permissions (duplicateProfile)');
        $this->addAclPermission('exportProfile', 'profile', 'Insufficient Permissions (exportProfile)');
        $this->addAclPermission('importProfile', 'profile', 'Insufficient Permissions (importProfile)');
        $this->addAclPermission('createNode', 'export', 'Insufficient Permissions (createNode)');
        $this->addAclPermission('updateNode', 'export', 'Insufficient Permissions (updateNode)');
        $this->addAclPermission('deleteNode', 'export', 'Insufficient Permissions (deleteNode)');
        $this->addAclPermission('getSections', 'profile', 'Insufficient Permissions (getSections)');
        $this->addAclPermission('getColumns', 'profile', 'Insufficient Permissions (getColumns)');
        $this->addAclPermission('getParentKeys', 'profile', 'Insufficient Permissions (getParentKeys)');
    }

    /**
     * Returns all profiles into an array
     */
    public function getProfilesAction(): void
    {
        $manager = $this->getModelManager();

        $query = $this->profileRepository->getProfilesListQuery(
            $this->Request()->getParam('filter', []),
            $this->Request()->getParam('sort', []),
            $this->Request()->getParam('limit') ? (int) $this->Request()->getParam('limit') : null,
            $this->Request()->getParam('start') ? (int) $this->Request()->getParam('start') : null
        )->getQuery();

        $count = $manager->getQueryCount($query);

        $data = $query->getArrayResult();
        $namespace = $this->snippetManager->getNamespace('backend/swag_import_export/default_profiles');

        foreach ($data as &$profile) {
            if ($profile['default'] === true) {
                $profile['translation'] = $namespace->get($profile['name']);
                $profile['description'] = $namespace->get($profile['description']);
            }
        }

        $this->View()->assign([
            'success' => true, 'data' => $data, 'total' => $count,
        ]);
    }

    /**
     * @return \Enlight_View|\Enlight_View_Default
     */
    public function createProfilesAction()
    {
        $data = $this->Request()->getParam('data', 1);

        try {
            $profileEntity = $this->profileFactory->createProfileModel($data);

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

    public function deleteProfilesAction(): void
    {
        $manager = $this->getModelManager();
        $data = $this->Request()->getParam('data', 1);

        if (isset($data['id'])) {
            $data = [$data];
        }

        try {
            foreach ($data as $profile) {
                $profileEntity = $this->getProfile((int) $profile['id']);
                $manager->remove($profileEntity);
            }
            $manager->flush();
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
            ]);

            return;
        }

        $this->View()->assign(['success' => true]);
    }

    public function updateProfilesAction(): void
    {
        $data = $this->Request()->getParam('data', 1);

        $profileEntity = $this->getModelManager()->getRepository(Profile::class)->findOneBy(['id' => $data['id']]);

        if (!$profileEntity) {
            throw new ModelNotFoundException(Profile::class, $data['id']);
        }

        $profileEntity->setName($data['name']);

        $this->saveProfile($profileEntity);
    }

    public function getProfileAction(): void
    {
        $profileId = (int) $this->Request()->getParam('profileId', -1);

        if ($profileId === -1) {
            $this->View()->assign(['success' => false, 'children' => []]);

            return;
        }

        $tree = $this->getProfile($profileId)->getTree();
        $root = TreeHelper::convertToExtJSTree(\json_decode($tree, true));

        $this->View()->assign(['success' => true, 'children' => $root]);
    }

    public function duplicateProfileAction(): void
    {
        $profileId = (int) $this->Request()->getParam('profileId');
        $loadedProfile = $this->getProfile($profileId);

        $profile = new Profile();

        $profile->setBaseProfile($loadedProfile->getId());
        $profile->setName($loadedProfile->getName() . ' (' . $this->snippetManager->getNamespace('backend/swag_import_export/controller')->get('swag_import_export/profile/copy') . ')');
        $profile->setType($loadedProfile->getType());
        $profile->setTree($loadedProfile->getTree());

        $this->saveProfile($profile);
    }

    public function exportProfileAction(Request $request): void
    {
        if (!$request->get('profileId')) {
            $this->View()->assign([
                'success' => false,
                'msg' => 'No profile provided',
            ]);

            return;
        }

        $profileId = (int) $request->get('profileId');

        try {
            $exportDataStruct = $this->profileService->exportProfile($profileId);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
            ]);

            return;
        }
        $this->Front()->Plugins()->Json()->setRenderer(false);

        $profileName = \urlencode($exportDataStruct->getName());

        $response = $this->Response();
        $response->setHeader('Cache-Control', 'public');
        $response->setHeader('Content-Description', 'File Transfer');
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $profileName . '.json');
        $response->setHeader('Content-type', 'application/json');

        echo $exportDataStruct->getExportData();
    }

    public function importProfileAction(): void
    {
        $file = Request::createFromGlobals()->files->get('profilefile');

        try {
            $this->profileService->importProfile($file);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $this->View()->assign([
            'success' => true,
        ]);
    }

    public function createNodeAction(): void
    {
        $manager = $this->getModelManager();
        $profileId = (int) $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileEntity = $this->getProfile($profileId);

        $tree = \json_decode($profileEntity->getTree(), true);

        if (isset($data['parentId'])) {
            $data = [$data];
        }

        $errors = false;

        foreach ($data as &$node) {
            $node['id'] = \uniqid();
            if (!TreeHelper::appendNode($node, $tree)) {
                $errors = true;
            }
        }

        $profileEntity->setTree(\json_encode($tree, \JSON_THROW_ON_ERROR));

        $manager->persist($profileEntity);
        $manager->flush();

        if ($errors) {
            $this->View()->assign(['success' => false, 'message' => 'Some nodes could not be saved', 'children' => $data]);
        } else {
            $this->View()->assign(['success' => true, 'children' => $data]);
        }
    }

    public function updateNodeAction(): void
    {
        $manager = $this->getModelManager();
        $profileId = (int) $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileEntity = $this->getProfile($profileId);
        $profileType = $profileEntity->getType();
        $defaultFields = [];

        $dataManager = $this->dataProvider->createDataManager($profileType);
        if ($dataManager) {
            $defaultFields = $dataManager->getDefaultFields();
        }

        $tree = \json_decode($profileEntity->getTree(), true);

        if (isset($data['parentId'])) {
            $data = [$data];
        }

        $errors = false;

        foreach ($data as $node) {
            if (!TreeHelper::changeNode($node, $tree, $defaultFields)) {
                $errors = true;
                break;
            }

            // the root cannot be moved or deleted
            if ($node['id'] === 'root') {
                continue;
            }

            $changedNode = TreeHelper::getNodeById($node['id'], $tree);

            if (!\is_array($changedNode)) {
                throw new \RuntimeException('Node not found');
            }

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
        $reorderedTree = TreeHelper::reorderTree($tree);

        $profileEntity->setTree(\json_encode($reorderedTree, \JSON_THROW_ON_ERROR));

        $manager->persist($profileEntity);
        $manager->flush();

        if ($errors) {
            $this->View()->assign(['success' => false, 'message' => 'Some nodes could not be saved', 'children' => $data]);
        } else {
            $this->View()->assign(['success' => true, 'children' => $data]);
        }
    }

    public function deleteNodeAction(): void
    {
        $manager = $this->getModelManager();
        $profileId = (int) $this->Request()->getParam('profileId', 1);
        $data = $this->Request()->getParam('data', 1);
        $profileEntity = $this->getProfile($profileId);

        $tree = \json_decode($profileEntity->getTree(), true);

        if (isset($data['parentId'])) {
            $data = [$data];
        }

        $errors = false;

        foreach ($data as $node) {
            if (!TreeHelper::deleteNode($node, $tree)) {
                $errors = true;
            }
        }

        $profileEntity->setTree(\json_encode($tree, \JSON_THROW_ON_ERROR));

        $manager->persist($profileEntity);
        $manager->flush();

        if ($errors) {
            $this->View()->assign(['success' => false, 'message' => 'Some nodes could not be saved', 'children' => $data]);
        } else {
            $this->View()->assign(['success' => true, 'children' => $data]);
        }
    }

    public function getSectionsAction(): void
    {
        $postData['profileId'] = (int) $this->Request()->getParam('profileId');

        if (!$postData['profileId']) {
            $this->View()->assign([
                'success' => false, 'message' => 'No profile Id',
            ]);

            return;
        }

        $type = $this->profileFactory->loadProfile($postData['profileId'])->getType();

        $sections = $this->dataProvider->createDbAdapter($type)->getSections();

        $this->View()->assign([
            'success' => true,
            'data' => $sections,
            'total' => \count($sections),
        ]);
    }

    public function getColumnsAction(): void
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');
        $section = $this->Request()->getParam('adapter', 'default');

        if (!$postData['profileId']) {
            $this->View()->assign([
                'success' => false, 'message' => 'No profile Id',
            ]);

            return;
        }

        $type = $this->profileFactory->loadProfile((int) $postData['profileId'])->getType();

        $dbAdapter = $this->dataProvider->createDbAdapter($type);
        $dataManager = $this->dataProvider->createDataManager($type);

        $defaultFieldsName = null;
        $defaultFields = [];
        if ($dataManager) {
            $defaultFields = $dataManager->getDefaultFields();
            if (method_exists($dataManager, 'getDefaultFieldsName')) {
                $defaultFieldsName = $dataManager->getDefaultFieldsName();
            }
        }

        $columns = $dbAdapter->getColumns($section);

        if (empty($columns)) {
            $this->View()->assign([
                'success' => false, 'msg' => 'No columns found.',
            ]);
        }

        // merge all sections
        if ($section === 'default' && \count($dbAdapter->getSections()) > 1) {
            $columns = \array_reduce($columns, function ($carry, $item) {
                if (\is_string($item)) {
                    $item = [$item];
                }

                return \array_merge($carry, $item);
            }, []);
        }

        foreach ($columns as &$column) {
            $match = '';
            \preg_match('/(?<=as ).*/', $column, $match);

            if (!$match[0]) {
                continue;
            }
            $match = \trim($match[0]);

            if ($match != '') {
                $column = $match;
            } else {
                \preg_match('/(?<=\.).*/', $column, $match);
                $match = \trim($match[0]);
                if ($match != '') {
                    $column = $match;
                }
            }

            $column = ['id' => $column, 'name' => $column];

            if ($defaultFieldsName && \in_array($column['name'], $defaultFieldsName, true)) {
                $column['default'] = true;
                $column['type'] = DataManager::getFieldType($column['name'], $defaultFields);
            }
        }

        $this->View()->assign([
            'success' => true, 'data' => $columns, 'total' => \count($columns),
        ]);
    }

    public function getParentKeysAction(): void
    {
        $postData['profileId'] = $this->Request()->getParam('profileId');
        $section = $this->Request()->getParam('adapter', 'default');

        if (!$postData['profileId']) {
            $this->View()->assign([
                'success' => false, 'message' => 'No profile Id',
            ]);

            return;
        }

        $type = $this->profileFactory->loadProfile((int) $postData['profileId'])->getType();

        $dbAdapter = $this->dataProvider->createDbAdapter($type);

        if (!\method_exists($dbAdapter, 'getParentKeys')) {
            $this->View()->assign([
                'success' => true, 'data' => [], 'total' => 0,
            ]);

            return;
        }

        try {
            $columns = $dbAdapter->getParentKeys($section);
        } catch (\RuntimeException $e) {
            $this->View()->assign([
               'success' => false, 'message' => $e->getMessage(),
            ]);

            return;
        }

        foreach ($columns as &$column) {
            $match = '';
            \preg_match('/(?<=as ).*/', $column, $match);

            $match = \trim($match[0]);

            if ($match != '') {
                $column = $match;
            } else {
                \preg_match('/(?<=\.).*/', $column, $match);
                $match = \trim($match[0]);
                if ($match != '') {
                    $column = $match;
                }
            }

            $column = ['id' => $column, 'name' => $column];
        }

        $this->View()->assign([
            'success' => true, 'data' => $columns, 'total' => \count($columns),
        ]);
    }

    private function saveProfile(Profile $profile): void
    {
        $manager = $this->getModelManager();
        $manager->persist($profile);
        try {
            $manager->flush();
        } catch (DBALException $e) {
            $this->View()->assign([
                'success' => false,
                'message' => $this->snippetManager->getNamespace('backend/swag_import_export/controller')
                    ->get('swag_import_export/profile/duplicate_unique_error_msg'),
            ]);

            return;
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            return;
        }

        $this->View()->assign([
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

    private function getProfile(int $profileId): Profile
    {
        $profile = $this->getModelManager()->getRepository(Profile::class)->find($profileId);
        if (!$profile instanceof Profile) {
            throw new ModelNotFoundException(Profile::class, $profileId);
        }

        return $profile;
    }
}
