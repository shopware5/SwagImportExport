<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Shopware\Commands\SwagImportExport\ExportCommand;
use Shopware\Commands\SwagImportExport\ImportCommand;
use Shopware\Commands\SwagImportExport\ProfilesCommand;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\DataTransformerFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\Factories\ProfileFactory;

/**
 * Shopware SwagImportExport Plugin - Bootstrap
 *
 * @category  Shopware
 * @package   Shopware\Plugins\Backend\SwagImportExport
 * @copyright  Copyright (c) shopware AG (http://www.shopware.com)
 */
final class Shopware_Plugins_Backend_SwagImportExport_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var DataFactory $dataFactory
     */
    private $dataFactory;

    /**
     * @var ProfileFactory $profileFactory
     */
    private $profileFactory;

    /**
     * @var FileIOFactory $fileIOFactory
     */
    private $fileIOFactory;

    /**
     * @var DataTransformerFactory $dataTransformerFactory
     */
    private $dataTransformerFactory;

    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql $db
     */
    private $db;

    /**
     * @var ModelManager $em
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
     * @return string
     * @throws Exception
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'plugin.json'), true);

        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
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

        parent::afterInit();
    }

    /**
     * Install function of the plugin bootstrap.
     *
     * Registers all necessary components and dependencies.
     *
     * @return bool
     * @throws Exception
     */
    public function install()
    {
        // Check if Shopware version matches
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new Exception("This plugin requires Shopware 5.1.0 or a later version");
        }

        $this->createDatabase();
        $this->createMenu();
        $this->createAclResource();
        $this->registerEvents();
        $this->createDirectories();
        $this->createConfiguration();

        return true;
    }

    /**
     * @param string $oldVersion
     * @return bool
     * @throws Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function update($oldVersion)
    {
        // Check if Shopware version matches
        if (!$this->assertMinimumVersion('5.2.0')) {
            throw new Exception("This plugin requires Shopware 5.1.0 or a later version");
        }

        $this->createAclResource();
        $this->registerEvents();
        $this->createDirectories();
        $this->createConfiguration();

        if ($oldVersion == '1.0.0' || $oldVersion == '1.0.1') {
            //changing the name
            $this->db->update(
                's_core_menu',
                array('name' => 'Import/Export Advanced'),
                array("controller = 'SwagImportExport'")
            );

            $sql = "SELECT id FROM `s_core_menu` WHERE controller = 'ImportExport'";
            $menuItem = $this->db->fetchOne($sql);
            if (!$menuItem) {
                //inserting old menu item
                $sql = "INSERT INTO `s_core_menu`
                        (`parent`, `name`, `onclick`, `class`, `position`, `active`, `pluginID`, `controller`, `shortcut`, `action`)
                        VALUES
                        (7, 'Import/Export', '', 'sprite-arrow-circle-double-135', 3, 1, NULL, 'ImportExport', NULL, 'Index')";
                $this->db->query($sql);
            }

            //removing snippets
            $this->db->delete('s_core_snippets', array("value = 'Import/Export'"));

            $this->db->exec('ALTER TABLE `s_import_export_profile` ADD `hidden` INT NOT NULL');
            $this->db->exec('ALTER TABLE `s_import_export_log` CHANGE `message` `message` TEXT NULL');
            $this->db->exec('ALTER TABLE `s_import_export_log` CHANGE `state` `state` VARCHAR(100) NULL');

            $this->db->exec(
                'ALTER TABLE `s_import_export_session`
                    ADD COLUMN `log_id` INT NULL AFTER `profile_id`,
                    ADD CONSTRAINT FK_SWAG_IE_LOG_ID UNIQUE (`log_id`),
                    ADD FOREIGN KEY (`log_id`) REFERENCES `s_import_export_log` (`id`)'
            );

            $this->get('shopware.cache_manager')->clearProxyCache();
        }

        return true;
    }

    /**
     * Uninstall function of the plugin.
     * Fired from the plugin manager.
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->removeDatabaseTables();

        return true;
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

        $this->Application()->Loader()->registerNamespace('Shopware\Components', $this->Path() . 'Components/');
        $this->Application()->Loader()->registerNamespace('Shopware\Commands', $this->Path() . 'Commands/');
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
     * @return DataFactory
     */
    public function getDataFactory()
    {
        if ($this->dataFactory === null) {
            $this->registerMyNamespace();
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
            $this->registerMyNamespace();
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
            $this->registerMyNamespace();
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
            $this->registerMyNamespace();
            $this->dataTransformerFactory = Enlight_Class::Instance('Shopware\Components\SwagImportExport\Factories\DataTransformerFactory');
        }

        return $this->dataTransformerFactory;
    }

    /**
     * @param string $version
     * @return bool
     */
    public function checkMinVersion($version)
    {
        return $this->assertMinimumVersion($version);
    }

    /**
     * Creates the plugin database table over the doctrine schema tool.
     */
    private function createDatabase()
    {
        $tool = new SchemaTool($this->em);

        $classes = array(
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Session'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Logger'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Profile'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Expression')
        );

        try {
            $tool->createSchema($classes);
        } catch (ToolsException $e) {
        }
    }

    /**
     * Removes the plugin database tables
     */
    private function removeDatabaseTables()
    {
        $tool = new SchemaTool($this->em);

        $classes = array(
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Session'),
            $this->em->getClassMetadata('Shopware\CustomModels\ImportExport\Logger')
        );

        $tool->dropSchema($classes);
    }

    /**
     * Creates the Swag Import Export backend menu item.
     */
    public function createMenu()
    {
        $this->createMenuItem(
            array(
                'label' => 'Import/Export Advanced',
                'controller' => 'SwagImportExport',
                'class' => 'sprite-server--plus',
                'action' => 'Index',
                'active' => 1,
                'parent' => $this->Menu()->findOneBy(array('label' => 'Inhalte')),
                'position' => 6,
            )
        );
    }

    /**
     * Registers all necessary events.
     */
    protected function registerEvents()
    {
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExport',
            'getBackendController'
        );
        $this->subscribeEvent(
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportCron',
            'getCronjobController'
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
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_SwagImportExport',
            'getFrontendController'
        );
    }

    /**
     * Returns the path to the backend controller.
     *
     * @return string
     */
    public function getBackendController()
    {
        $this->registerMyNamespace();
        $this->addConfigDirs();

        return $this->Path() . '/Controllers/Backend/SwagImportExport.php';
    }

    /**
     * Returns the path to the CronJob controller.
     *
     * @return string
     */
    public function getCronjobController()
    {
        $this->registerMyNamespace();
        $this->addConfigDirs();

        return $this->Path() . '/Controllers/Backend/SwagImportExportCron.php';
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
     * Returns the path to the frontend controller.
     *
     * @return string
     */
    public function getFrontendController()
    {
        $this->registerMyNamespace();

        return $this->Path() . '/Controllers/Frontend/SwagImportExport.php';
    }

    /**
     * Adds the console commands (sw:import and sw:export)
     *
     * @return ArrayCollection
     */
    public function onAddConsoleCommand()
    {
        $this->registerMyNamespace();

        return new ArrayCollection(
            array(
                new ImportCommand(),
                new ExportCommand(),
                new ProfilesCommand()
            )
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
            array(
                'label' => 'Continue import/export if an error occurs during the process',
                'store' => array(
                    array(false, 'No'),
                    array(true, 'Yes')
                ),
                'required' => false,
                'multiSelect' => false,
                'value' => false
            )
        );

        $form->setElement(
            'combo',
            'SwagImportExportImageMode',
            array(
                'label' => 'Image import mode',
                'store' => array(
                    array(1, 'Gleiche Artikelbilder erneut verwenden (Re-use same article images)'),
                    array(2, 'Gleiche Artikelbilder nicht erneut verwenden (Don\'t re-use article images)')
                ),
                'required' => false,
                'multiSelect' => false,
                'value' => 2
            )
        );

        $this->createTranslations();
    }

    /**
     * Translation for plugin configuration
     */
    public function createTranslations()
    {
        $translations = array(
            'en_GB' => array(
                'SwagImportExportImageMode' => array(
                    'label' => 'Image import mode'
                ),
                'SwagImportExportErrorMode' => array(
                    'label' => 'Continue import/export if an error occurs during the process'
                )
            ),
            'de_DE' => array(
                'SwagImportExportImageMode' => array(
                    'label' => 'Bildimport-Modus'
                ),
                'SwagImportExportErrorMode' => array(
                    'label' => 'Mit Import/Export fortfahren, wenn ein Fehler auftritt.'
                )
            ),
        );

        if ($this->assertMinimumVersion('4.2.2')) {
            $this->addFormTranslations($translations);
        }
    }

    private function createAclResource()
    {
        // If exists: find existing SwagImportExport resource
        $pluginId = $this->db->fetchRow(
            'SELECT pluginID FROM s_core_acl_resources WHERE name = ? ',
            array("swagimportexport")
        );
        $pluginId = isset($pluginId['pluginID']) ? $pluginId['pluginID'] : null;

        if ($pluginId) {
            // prevent creation of new acl resource
            return;
        }

        $resource = new \Shopware\Models\User\Resource();
        $resource->setName('swagimportexport');
        $resource->setPluginId($this->getId());

        foreach (array('export', 'import', 'profile', 'read') as $action) {
            $privilege = new \Shopware\Models\User\Privilege();
            $privilege->setResource($resource);
            $privilege->setName($action);

            $this->em->persist($privilege);
        }

        $this->em->persist($resource);

        $this->em->flush();
    }

    private function addConfigDirs()
    {
        /** @var Shopware_Components_Snippet_Manager $snippetManager */
        $snippetManager = $this->get('snippets');
        $snippetManager->addConfigDir($this->Path() . 'Snippets/');

        /** @var Enlight_Template_Manager $templateManager */
        $templateManager = $this->get('template');
        $templateManager->addTemplateDir($this->Path() . 'Views/');
    }
}
