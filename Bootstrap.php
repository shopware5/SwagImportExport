<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\SchemaTool;
use Shopware\Commands\SwagImportExport\ExportCommand;
use Shopware\Commands\SwagImportExport\ImportCommand;
use Shopware\Commands\SwagImportExport\ProfilesCommand;
use Shopware\Components\CacheManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\DataTransformerFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\Factories\ProfileFactory;
use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Service\AutoImportService;
use Shopware\Components\SwagImportExport\Service\AutoImportServiceInterface;
use Shopware\Components\SwagImportExport\Service\ExportService;
use Shopware\Components\SwagImportExport\Service\ImportService;
use Shopware\Components\SwagImportExport\Service\ProfileService;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\FileHelper;
use Shopware\Setup\SwagImportExport\Exception\MinVersionException;
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
use Shopware\Setup\SwagImportExport\Update\UpdaterInterface;

/**
 * Shopware SwagImportExport Plugin - Bootstrap
 *
 * @category  Shopware
 *
 * @copyright  Copyright (c) shopware AG (http://www.shopware.com)
 */
final class Shopware_Plugins_Backend_SwagImportExport_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var ProfileFactory
     */
    private $profileFactory;

    /**
     * @var FileIOFactory
     */
    private $fileIOFactory;

    /**
     * @var DataTransformerFactory
     */
    private $dataTransformerFactory;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var ModelManager
     */
    private $em;

    /**
     * Returns the plugin label which is displayed in the plugin information and
     * in the Plugin Manager.
     *
     * @return string
     */
    public function getLabel()
    {
        return 'Shopware Import/Export';
    }

    /**
     * Returns the version of the plugin as a string
     *
     * @throws Exception
     *
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        }
        throw new Exception('The plugin has an invalid version file.');
    }

    /**
     * After init event of the bootstrap class.
     *
     * The afterInit function registers the custom plugin models.
     */
    public function afterInit()
    {
        $this->db = $this->get('db');
        $this->em = $this->get('models');

        $this->registerCustomModels();
        $this->registerMyNamespace();
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities()
    {
        return [
            'install' => true,
            'update' => true,
            'enable' => true,
            'secureUninstall' => true,
        ];
    }

    /**
     * Install function of the plugin bootstrap.
     *
     * Registers all necessary components and dependencies.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function install()
    {
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new MinVersionException('This plugin requires Shopware 5.2.0 or a later version');
        }
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->get('shopware.cache_manager');
        $cacheManager->clearProxyCache();

        $setupContext = new SetupContext(
            $this->get('config')->get('version'),
            $this->getVersion(),
            SetupContext::NO_PREVIOUS_VERSION
        );

        $installers = [];
        $installers[] = new DefaultProfileInstaller($setupContext, $this->get('dbal_connection'));
        $installers[] = new MainMenuItemInstaller($setupContext, $this->get('models'));
        $installers[] = new OldAdvancedMenuInstaller($setupContext, $this->get('models'));

        $this->createDatabase();
        $this->createAclResource();
        $this->registerControllers();
        $this->registerEvents();
        $this->createDirectories();
        $this->createConfiguration();
        $this->createCronJobs();

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
     * Event listener for the auto import cron job
     */
    public function onCronImport()
    {
        /** @var AutoImportServiceInterface $importService */
        $importService = $this->get('swag_import_export.auto_importer');
        $importService->runAutoImport();
    }

    /**
     * @param string $oldVersion
     *
     * @throws MinVersionException
     * @throws Exception
     * @throws Zend_Db_Adapter_Exception
     *
     * @return array
     */
    public function update($oldVersion)
    {
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new MinVersionException('This plugin requires Shopware 5.2.0 or a later version');
        }

        if (version_compare($oldVersion, '2.0.0', '<=')) {
            $this->renameDuplicateProfileNames();
        }
        /** @var CacheManager $cacheManager */
        $cacheManager = $this->get('shopware.cache_manager');
        $cacheManager->clearProxyCache();

        $connection = $this->get('dbal_connection');
        $connection->executeQuery('SET foreign_key_checks = 0;');

        $setupContext = new SetupContext(
            $this->get('config')->get('version'),
            $this->getVersion(),
            $oldVersion
        );

        $updaters = [];
        $updaters[] = new Update01MainMenuItem($setupContext, $this->get('models'));
        $updaters[] = new Update02RemoveForeignKeyConstraint(
            $setupContext,
            $this->get('dbal_connection'),
            $this->get('models'),
            $this->get('dbal_connection')->getSchemaManager()
        );
        $updaters[] = new Update03DefaultProfileSupport($setupContext, $this->get('dbal_connection'), $this->get('snippets'));
        $updaters[] = new Update04CreateColumns($setupContext, $this->get('dbal_connection'));
        $updaters[] = new DefaultProfileUpdater($setupContext, $this->get('dbal_connection'), $this->get('snippets'));

        $this->registerControllers();
        $this->createAclResource();
        $this->registerEvents();
        $this->createDirectories();
        $this->createConfiguration();

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

        $defaultProfileInstaller = new DefaultProfileInstaller($setupContext, $this->get('dbal_connection'));
        $defaultProfileInstaller->install();

        return [
            'success' => true,
            'invalidateCache' => $this->getInvalidateCacheArray(),
        ];
    }

    /**
     * Uninstall function of the plugin.
     * Fired from the plugin manager.
     *
     * @return array
     */
    public function uninstall()
    {
        $this->secureUninstall();

        $this->removeDatabaseTables();
        $this->removeAclResource();

        return [
            'success' => true,
            'invalidateCache' => $this->getInvalidateCacheArray(),
        ];
    }

    /**
     * @return array
     */
    public function secureUninstall()
    {
        return [
            'success' => true,
            'invalidateCache' => $this->getInvalidateCacheArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function enable()
    {
        return [
            'success' => true,
            'invalidateCache' => $this->getInvalidateCacheArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function disable()
    {
        return [
            'success' => true,
            'invalidateCache' => $this->getInvalidateCacheArray(),
        ];
    }

    /**
     * Register components directory
     */
    public function registerMyNamespace()
    {
        // Register Doctrine RegExp extension
        /** @var Configuration $config */
        $config = $this->em->getConfiguration();
        $classLoader = new \Doctrine\Common\ClassLoader('DoctrineExtensions', $this->Path() . 'Components/');
        $classLoader->register();
        $config->addCustomStringFunction('GroupConcat', 'DoctrineExtensions\Query\Mysql\GroupConcat');

        $this->get('loader')->registerNamespace('Shopware\Components', $this->Path() . 'Components/');
        $this->get('loader')->registerNamespace('Shopware\Commands', $this->Path() . 'Commands/');
        $this->get('loader')->registerNamespace('Shopware\Subscriber', $this->Path() . 'Subscriber/');
        $this->get('loader')->registerNamespace('Shopware\Setup', $this->Path() . 'Setup/');
    }

    /**
     * @return DataFactory
     */
    public function getDataFactory()
    {
        if ($this->dataFactory === null) {
            $this->dataFactory = Enlight_Class::Instance('Shopware\Components\SwagImportExport\Factories\DataFactory');
        }

        return $this->dataFactory;
    }

    /**
     * @return ProfileFactory
     */
    public function getProfileFactory()
    {
        if ($this->profileFactory === null) {
            $this->profileFactory = Enlight_Class::Instance('Shopware\Components\SwagImportExport\Factories\ProfileFactory');
        }

        return $this->profileFactory;
    }

    /**
     * @return FileIOFactory
     */
    public function getFileIOFactory()
    {
        if ($this->fileIOFactory === null) {
            $this->fileIOFactory = Enlight_Class::Instance('Shopware\Components\SwagImportExport\Factories\FileIOFactory');
        }

        return $this->fileIOFactory;
    }

    /**
     * @return DataTransformerFactory
     */
    public function getDataTransformerFactory()
    {
        if ($this->dataTransformerFactory === null) {
            $this->dataTransformerFactory = Enlight_Class::Instance('Shopware\Components\SwagImportExport\Factories\DataTransformerFactory');
        }

        return $this->dataTransformerFactory;
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    public function checkMinVersion($version)
    {
        return $this->assertMinimumVersion($version);
    }

    /**
     * @return UploadPathProvider
     */
    public function registerUploadPathProvider()
    {
        return new UploadPathProvider(Shopware()->DocPath());
    }

    /**
     * @return AutoImportService
     */
    public function registerAutoImportService()
    {
        return new AutoImportService(
            $this->get('swag_import_export.upload_path_provider'),
            $this->get('models'),
            $this->getProfileFactory()
        );
    }

    /**
     * @return Logger
     */
    public function registerLogger()
    {
        return new Logger(
            $this->get('swag_import_export.csv_file_writer'),
            $this->get('models')
        );
    }

    /**
     * @return ImportService
     */
    public function registerImportService()
    {
        return new ImportService(
            $this->getProfileFactory(),
            $this->getFileIOFactory(),
            $this->getDataFactory(),
            $this->getDataTransformerFactory(),
            $this->get('swag_import_export.logger'),
            $this->get('swag_import_export.upload_path_provider'),
            Shopware()->Auth(),
            $this->get('shopware_media.media_service')
        );
    }

    /**
     * @return ExportService
     */
    public function registerExportService()
    {
        return new ExportService(
            $this->getProfileFactory(),
            $this->getFileIOFactory(),
            $this->getDataFactory(),
            $this->getDataTransformerFactory(),
            $this->get('swag_import_export.logger'),
            $this->get('swag_import_export.upload_path_provider'),
            Shopware()->Auth(),
            $this->get('shopware_media.media_service')
        );
    }

    public function registerProfileService()
    {
        return new ProfileService(
            $this->get('models'),
            new Symfony\Component\Filesystem\Filesystem(),
            $this->get('snippets')
        );
    }

    /**
     * @return CsvFileReader
     */
    public function registerCsvFileReader()
    {
        return new CsvFileReader(
            $this->get('swag_import_export.upload_path_provider')
        );
    }

    /**
     * @return CsvFileWriter
     */
    public function registerCsvFileWriter()
    {
        return new CsvFileWriter(
            new FileHelper()
        );
    }

    /**
     * Injects Ace Editor used in Conversions GUI
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function injectBackendAceEditor(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Backend_Index $controller */
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate()) {
            return;
        }

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('backend/swag_import_export/menu_entry.tpl');
    }

    /**
     * Adds the console commands (sw:import and sw:export)
     *
     * @return ArrayCollection
     */
    public function onAddConsoleCommand()
    {
        return new ArrayCollection(
            [
                new ImportCommand(),
                new ExportCommand(),
                new ProfilesCommand(),
            ]
        );
    }

    /**
     * Create plugin configuration
     */
    public function createConfiguration()
    {
        $form = $this->Form();

        $form->setElement(
            'combo',
            'SwagImportExportErrorMode',
            [
                'label' => 'Continue import/export if an error occurs during the process',
                'store' => [
                    [false, ['de_DE' => 'Nein', 'en_GB' => 'No']],
                    [true, ['de_DE' => 'Ja', 'en_GB' => 'Yes']],
                ],
                'required' => false,
                'multiSelect' => false,
                'value' => false,
            ]
        );
        
      $form->setElement(
            'text',
            'batch-size',
            array(
                'label' => 'Batch-Size',
                'value' => '1000'
            )
        );

        $form->setElement(
            'combo',
            'SwagImportExportImageMode',
            [
                'label' => 'Image import mode',
                'store' => [
                    [1, ['de_DE' => 'Gleiche Artikelbilder erneut verwenden', 'en_GB' => 'Re-use same article images']],
                    [2, ['de_DE' => 'Gleiche Artikelbilder nicht erneut verwenden', 'en_GB' => 'Don\'t re-use article images']],
                ],
                'required' => false,
                'multiSelect' => false,
                'value' => 2,
            ]
        );

        $form->setElement(
            'checkbox',
            'useCommaDecimal',
            [
                'label' => 'Use comma as decimal separator',
                'value' => false,
            ]
        );

        $this->createTranslations();
    }

    /**
     * Translation for plugin configuration
     */
    public function createTranslations()
    {
        $translations = [
            'en_GB' => [
                'SwagImportExportImageMode' => [
                    'label' => 'Image import mode',
                ],
                'SwagImportExportErrorMode' => [
                    'label' => 'Continue import/export if an error occurs during the process',
                ],
                'useCommaDecimal' => [
                    'label' => 'Use comma as decimal separator',
                ],
            ],
            'de_DE' => [
                'SwagImportExportImageMode' => [
                    'label' => 'Bildimport-Modus',
                ],
                'SwagImportExportErrorMode' => [
                    'label' => 'Mit Import/Export fortfahren, wenn ein Fehler auftritt.',
                ],
                'useCommaDecimal' => [
                    'label' => 'Komma als Dezimal-Trennzeichen nutzen',
                ],
            ],
        ];

        if ($this->assertMinimumVersion('4.2.2')) {
            $this->addFormTranslations($translations);
        }
    }

    /**
     * Registers all necessary events.
     */
    protected function registerEvents()
    {
        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.csv_file_writer',
            'registerCsvFileWriter'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.csv_file_reader',
            'registerCsvFileReader'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.logger',
            'registerLogger'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.import_service',
            'registerImportService'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.export_service',
            'registerExportService'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.profile_service',
            'registerProfileService'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.upload_path_provider',
            'registerUploadPathProvider'
        );

        $this->subscribeEvent(
            'Enlight_Bootstrap_InitResource_swag_import_export.auto_importer',
            'registerAutoImportService'
        );

        $this->subscribeEvent(
            'Enlight_Controller_Action_PostDispatch_Backend_Index',
            'injectBackendAceEditor'
        );

        $this->subscribeEvent(
            'Shopware_Console_Add_Command',
            'onAddConsoleCommand'
        );

        $this->subscribeEvent(
            'Shopware_CronJob_CronAutoImport',
            'onCronImport'
        );
    }

    protected function registerControllers()
    {
        $backendControllers = [
            'SwagImportExport',
            'SwagImportExportImport',
            'SwagImportExportExport',
            'SwagImportExportProfile',
            'SwagImportExportConversion',
            'SwagImportExportSession',
            'SwagImportExportCron',
        ];

        foreach ($backendControllers as $ctrl) {
            $this->registerController('Backend', $ctrl);
        }

        $this->registerController('Frontend', 'SwagImportExport');
    }

    /**
     * creates the import export auto import cronJob
     */
    private function createCronJobs()
    {
        $this->createCronJob(
            'ImportExport - AutoImport',
            'CronAutoImport',
            '86400',
            true
        );
    }

    private function createDirectories()
    {
        $importCronPath = Shopware()->DocPath() . 'files/import_cron/';
        if (!file_exists($importCronPath)) {
            mkdir($importCronPath, 0777, true);
        }

        $importExportPath = Shopware()->DocPath() . 'files/import_export/';
        if (!file_exists($importExportPath)) {
            mkdir($importExportPath, 0777, true);
        }
    }

    /**
     * Creates the plugin database table over the doctrine schema tool.
     */
    private function createDatabase()
    {
        $schemaTool = new SchemaTool($this->em);
        $doctrineModels = $this->getDoctrineModels();

        $tableNames = $this->removeTablePrefix($schemaTool, $doctrineModels);

        /** @var ModelManager $modelManger */
        $modelManger = $this->get('models');
        $schemaManager = $modelManger->getConnection()->getSchemaManager();
        if (!$schemaManager->tablesExist($tableNames)) {
            $schemaTool->createSchema($doctrineModels);
        }
    }

    private function updateDatabase()
    {
        $schemaTool = new SchemaTool($this->em);
        $doctrineModels = $this->getDoctrineModels();
        $schemaTool->updateSchema($doctrineModels, true);
    }

    /**
     * Removes the plugin database tables
     */
    private function removeDatabaseTables()
    {
        $tool = new SchemaTool($this->em);
        $classes = $this->getDoctrineModels();
        $tool->dropSchema($classes);
    }

    private function createAclResource()
    {
        // If exists: find existing SwagImportExport resource
        $pluginId = $this->db->fetchRow(
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
        $resource->setPluginId($this->getId());

        foreach (['export', 'import', 'profile', 'read'] as $action) {
            $privilege = new \Shopware\Models\User\Privilege();
            $privilege->setResource($resource);
            $privilege->setName($action);

            $this->em->persist($privilege);
        }

        $this->em->persist($resource);

        $this->em->flush();
    }

    private function removeAclResource()
    {
        $sql = 'SELECT id FROM s_core_acl_resources
                WHERE pluginID = ?;';

        $resourceId = $this->db->fetchOne($sql, [$this->getId()]);

        if (!$resourceId) {
            return;
        }

        $resource = $this->em->getRepository(\Shopware\Models\User\Resource::class)->find($resourceId);
        foreach ($resource->getPrivileges() as $privilege) {
            $this->em->remove($privilege);
        }

        $this->em->remove($resource);
        $this->em->flush();
    }

    /**
     * Helper method to return all the caches, that need to be cleared after
     * updating / uninstalling / enabling / disabling a plugin
     *
     * @return array
     */
    private function getInvalidateCacheArray()
    {
        return ['config', 'backend', 'proxy'];
    }

    /**
     * @return array
     */
    private function getDoctrineModels()
    {
        return [
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Session'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Logger'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Profile'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Expression'),
        ];
    }

    /**
     * @param SchemaTool $tool
     * @param array      $classes
     *
     * @return array
     */
    private function removeTablePrefix(SchemaTool $tool, array $classes)
    {
        $schema = $tool->getSchemaFromMetadata($classes);
        $tableNames = [];
        foreach ($schema->getTableNames() as $tableName) {
            $tableNames[] = array_pop(explode('.', $tableName));
        }

        return $tableNames;
    }

    /**
     * Rename duplicate profile names to prevent integrity constraint mysql exceptions.
     */
    private function renameDuplicateProfileNames()
    {
        $connection = $this->get('dbal_connection');
        $profiles = $connection->fetchAll('SELECT COUNT(id) as count, name FROM s_import_export_profile GROUP BY name');

        foreach ($profiles as $profile) {
            if ($profile['count'] == 1) {
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
        $dbalConnection = $this->get('dbal_connection');

        foreach ($profiles as $index => $profile) {
            if ($index === 0) {
                continue;
            }

            $dbalConnection->executeQuery(
                'UPDATE s_import_export_profile SET name = :name WHERE id=:id',
                ['name' => uniqid($profile['name'] . '_', true), 'id' => $profile['id']]
            );
        }
    }
}
