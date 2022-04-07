<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    throw new \Exception('Vendor is missing');
}

use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Components\CacheManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\CustomModels\ImportExport\Expression;
use Shopware\CustomModels\ImportExport\Logger as LoggerModel;
use Shopware\CustomModels\ImportExport\Profile;
use Shopware\CustomModels\ImportExport\Session;
use Shopware\Resources\Compiler\HookablePass;
use Shopware\Setup\SwagImportExport\Install\DefaultProfileInstaller;
use Shopware\Setup\SwagImportExport\Install\InstallerInterface;
use Shopware\Setup\SwagImportExport\Install\MainMenuItemInstaller;
use Shopware\Setup\SwagImportExport\Install\OldAdvancedMenuInstaller;
use Shopware\Setup\SwagImportExport\SetupContext;
use Shopware\Setup\SwagImportExport\Update\DefaultProfileUpdater;
use Shopware\Setup\SwagImportExport\Update\Update01MainMenuItem;
use Shopware\Setup\SwagImportExport\Update\Update02RemoveForeignKeyConstraint;
use Shopware\Setup\SwagImportExport\Update\Update03DefaultProfileSupport;
use Shopware\Setup\SwagImportExport\Update\Update04CreateColumns;
use Shopware\Setup\SwagImportExport\Update\Update05CreateCustomerCompleteProfile;
use Shopware\Setup\SwagImportExport\Update\Update06CreateCategoryTranslationProfile;
use Shopware\Setup\SwagImportExport\Update\UpdaterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwagImportExport extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new HookablePass());

        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context)
    {
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->container->get('shopware.cache_manager');
        $cacheManager->clearProxyCache();

        $setupContext = new SetupContext(
            $this->container->get('config')->get('version'),
            $context->getCurrentVersion(),
            SetupContext::NO_PREVIOUS_VERSION
        );



        $installers = [];
        $installers[] = new DefaultProfileInstaller($setupContext, $this->container->get('dbal_connection'));
        $installers[] = new MainMenuItemInstaller($setupContext, $this->container->get('models'));
        $installers[] = new OldAdvancedMenuInstaller($setupContext, $this->container->get('models'));

        $this->createDatabase();

        $this->createAclResource($context->getPlugin());

        $this->createDirectories();

        /** @var InstallerInterface $installer */
        foreach ($installers as $installer) {
            if (!$installer->isCompatible()) {
                continue;
            }
            $installer->install();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context)
    {
        $oldVersion = $context->getCurrentVersion();

        if (\version_compare($oldVersion, '2.0.0', '<=')) {
            $this->renameDuplicateProfileNames();
        }
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->container->get('shopware.cache_manager');
        $cacheManager->clearProxyCache();

        $connection = $this->container->get('dbal_connection');
        $connection->executeQuery('SET foreign_key_checks = 0;');

        $setupContext = new SetupContext(
            $this->container->get('config')->get('version'),
            $context->getUpdateVersion(),
            $oldVersion
        );

        $updaters = [];
        $updaters[] = new Update01MainMenuItem($setupContext, $this->container->get('models'));
        $updaters[] = new Update02RemoveForeignKeyConstraint(
            $setupContext,
            $this->container->get('dbal_connection'),
            $this->container->get('models'),
            $this->container->get('dbal_connection')->getSchemaManager()
        );
        $updaters[] = new Update03DefaultProfileSupport($setupContext, $this->container->get('dbal_connection'), $this->container->get('snippets'));
        $updaters[] = new Update04CreateColumns($setupContext, $this->container->get('dbal_connection'));
        $updaters[] = new Update05CreateCustomerCompleteProfile($setupContext, $this->container->get('dbal_connection'));
        $updaters[] = new Update06CreateCategoryTranslationProfile($setupContext, $this->container->get('dbal_connection'));
        $updaters[] = new DefaultProfileUpdater($setupContext, $this->container->get('dbal_connection'));

        $this->createAclResource($context->getPlugin());
        $this->createDirectories();

        try {
            $this->updateDatabase();
        } catch (\Exception $e) {
        }

        /** @var UpdaterInterface $updater */
        foreach ($updaters as $updater) {
            if (!$updater->isCompatible()) {
                continue;
            }
            $updater->update();
        }
        $connection->executeQuery('SET foreign_key_checks = 1;');

        $defaultProfileInstaller = new DefaultProfileInstaller($setupContext, $this->container->get('dbal_connection'));
        $defaultProfileInstaller->install();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context)
    {
        $this->removeDatabaseTables();
        $this->removeAclResource($context->getPlugin());
    }


    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context)
    {
    }

    private function createDirectories()
    {
        $importCronPath = Shopware()->DocPath() . 'files/import_cron/';
        if (!\file_exists($importCronPath)) {
            \mkdir($importCronPath, 0777, true);
        }

        if (!\file_exists($importCronPath . '.htaccess')) {
            \copy($this->Path() . 'Setup/SwagImportExport/template', $importCronPath . '/.htaccess');
        }

        $importExportPath = Shopware()->DocPath() . 'files/import_export/';
        if (!\file_exists($importExportPath)) {
            \mkdir($importExportPath, 0777, true);
        }

        if (!\file_exists($importExportPath . '.htaccess')) {
            \copy($this->Path() . 'Setup/SwagImportExport/template', $importExportPath . '/.htaccess');
        }
    }


    /**
     * Creates the plugin database table over the doctrine schema tool.
     */
    private function createDatabase()
    {
        $schemaTool = new SchemaTool($this->getEntityManager());
        $doctrineModels = $this->getDoctrineModels();

        $tableNames = $this->removeTablePrefix($schemaTool, $doctrineModels);

        /** @var ModelManager $modelManger */
        $modelManger = $this->container->get('models');
        $schemaManager = $modelManger->getConnection()->getSchemaManager();
        if (!$schemaManager->tablesExist($tableNames)) {
            $schemaTool->createSchema($doctrineModels);
        }
    }

    private function updateDatabase()
    {
        $schemaTool = new SchemaTool($this->getEntityManager());
        $doctrineModels = $this->getDoctrineModels();
        $schemaTool->updateSchema($doctrineModels, true);
    }

    /**
     * Removes the plugin database tables
     */
    private function removeDatabaseTables()
    {
        $tool = new SchemaTool($this->getEntityManager());
        $classes = $this->getDoctrineModels();
        $tool->dropSchema($classes);
    }

    private function createAclResource(\Shopware\Models\Plugin\Plugin $plugin)
    {
        // If exists: find existing SwagImportExport resource
        $pluginId = $this->getDatabase()->fetchRow(
            'SELECT pluginID FROM s_core_acl_resources WHERE name = ? ',
            ['swagimportexport']
        );
        $pluginId = isset($pluginId['pluginID']) ? $pluginId['pluginID'] : null;

        if ($pluginId) {
            // prevent creation of new acl resource
            return;
        }

        $resource = new \Shopware\Models\User\Resource();
        $resource->setName('swagimportexport');
        $resource->setPluginId($plugin->getId());

        foreach (['export', 'import', 'profile', 'read'] as $action) {
            $privilege = new \Shopware\Models\User\Privilege();
            $privilege->setResource($resource);
            $privilege->setName($action);

            $this->getEntityManager()->persist($privilege);
        }

        $this->getEntityManager()->persist($resource);

        $this->getEntityManager()->flush();
    }

    private function removeAclResource(\Shopware\Models\Plugin\Plugin $plugin)
    {
        $sql = 'SELECT id FROM s_core_acl_resources
                WHERE pluginID = ?;';

        $resourceId = $this->getDatabase()->fetchOne($sql, [$plugin->getId()]);

        if (!$resourceId) {
            return;
        }

        $resource = $this->getEntityManager()->getRepository(\Shopware\Models\User\Resource::class)->find($resourceId);
        if ($resource === null) {
            return;
        }

        foreach ($resource->getPrivileges() as $privilege) {
            $this->getEntityManager()->remove($privilege);
        }

        $this->getEntityManager()->remove($resource);
        $this->getEntityManager()->flush();
    }


    private function getDoctrineModels(): array
    {
        return [
            $this->getEntityManager()->getClassMetadata(Session::class),
            $this->getEntityManager()->getClassMetadata(LoggerModel::class),
            $this->getEntityManager()->getClassMetadata(Profile::class),
            $this->getEntityManager()->getClassMetadata(Expression::class),
        ];
    }

    /**
     * @return array
     */
    private function removeTablePrefix(SchemaTool $tool, array $classes)
    {
        $schema = $tool->getSchemaFromMetadata($classes);
        $tableNames = [];
        foreach ($schema->getTableNames() as $tableName) {
            $explodedTableNames = \explode('.', $tableName);
            $tableNames[] = \array_pop($explodedTableNames);
        }

        return $tableNames;
    }

    /**
     * Rename duplicate profile names to prevent integrity constraint mysql exceptions.
     */
    private function renameDuplicateProfileNames()
    {
        $connection = $this->container->get('dbal_connection');
        $profiles = $connection->fetchAll('SELECT COUNT(id) as count, name FROM s_import_export_profile GROUP BY name');

        foreach ($profiles as $profile) {
            if ((int) $profile['count'] === 1) {
                continue;
            }

            $profilesWithSameName = $connection->fetchAll(
                'SELECT * FROM s_import_export_profile WHERE name=:name ORDER BY id',
                ['name' => $profile['name']]
            );

            $this->addSuffixToProfileNames($profilesWithSameName);
        }
    }

    /**
     * @param array $profiles
     */
    private function addSuffixToProfileNames($profiles)
    {
        $dbalConnection = $this->container->get('dbal_connection');

        foreach ($profiles as $index => $profile) {
            if ($index === 0) {
                continue;
            }

            $dbalConnection->executeQuery(
                'UPDATE s_import_export_profile SET name = :name WHERE id=:id',
                ['name' => \uniqid($profile['name'] . '_', true), 'id' => $profile['id']]
            );
        }
    }

    private function getEntityManager() {
        return $this->container->get('models');
    }

    private function getDatabase() {
        return $this->container->get('db');
    }
}
