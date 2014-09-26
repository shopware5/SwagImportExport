<?php

/**
 * Shopware 4.2
 * Copyright © shopware AG
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

use \Shopware\Components\SwagImportExport\Utils\SnippetsHelper as SnippetsHelper;
/**
 * Shopware SwagImportExport Plugin - Bootstrap
 *
 * @category  Shopware
 * @package   Shopware\Components\Console\Command
 * @copyright Copyright (c) 2014, shopware AG (http://www.shopware.de)
 */
class Shopware_Plugins_Backend_SwagImportExport_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * @var Shopware\Components\SwagImportExport\Factories\DataFactory
     */
    private $dataFactory;

    /**
     * @var Shopware\Components\SwagImportExport\Factories\ProfileFactory
     */
    private $profileFactory;

    /**
     * @var Shopware\Components\SwagImportExport\Factories\FileIOFactory
     */
    private $fileIOFactory;

    /**
     * @var Shopware\Components\SwagImportExport\Factories\DataTransformerFactory
     */
    private $dataTransformerFactory;

    /**
     * Returns the plugin label which is displayed in the plugin information and
     * in the Plugin Manager.
     * @return string
     */
    public function getLabel()
    {
        return 'Shopware Import/Export';
    }

    /**
     * Returns the current version of the plugin.
     * @return string
     */
    public function getVersion()
    {
        return "1.0.0";
    }

    /**
     * After init event of the bootstrap class.
     *
     * The afterInit function registers the custom plugin models.
     */
    public function afterInit()
    {
        $this->registerCustomModels();
    }

    /**
     * Install function of the plugin bootstrap.
     *
     * Registers all necessary components and dependencies.
     *
     * @return bool
     */
    public function install()
    {
        $this->createDatabase();
        $this->createMenu();
        $this->registerEvents();
        $this->registerCronJobs();
        $this->createDirectories();

        return true;
    }

    /**
     * Uninstall function of the plugin.
     * Fired from the plugin manager.
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
        $config = $this->Application()->Models()->getConfiguration();
        $classLoader = new \Doctrine\Common\ClassLoader('DoctrineExtensions', $this->Path() . 'Components/');
        $classLoader->register();
        $config->addCustomStringFunction('GroupConcat', 'DoctrineExtensions\Query\Mysql\GroupConcat');
        
        $this->Application()->Loader()->registerNamespace(
                'Shopware\Components', $this->Path() . 'Components/'
        );
        $this->Application()->Loader()->registerNamespace(
                'Shopware\Commands', $this->Path() . 'Commands/'
        );
    }

    /**
     * Register cron jobs
     */
    private function registerCronJobs()
    {
        $this->createCronJob(
                'ImportAction', 'ImportCron', 86400, true
        );

        $this->subscribeEvent(
                'Shopware_CronJob_ImportCron', 'onRunImportCronJob'
        );
    }
    
    private function createDirectories()
    {
        $importCron = Shopware()->DocPath() . 'files/import_cron/';
        mkdir($importCron, 0777, true);

        $importExport = Shopware()->DocPath() . 'files/import_export/';
        mkdir($importExport, 0777, true);
    }

    /**
     * Returns DataFactory
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
     * Returns ProfileFactory
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
     * Returns FileIOFactory
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
     * Returns DataTransformerFactory
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
     * Creates the plugin database table over the doctrine schema tool.
     */
    private function createDatabase()
    {
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);

        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Session'),
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Logger'),
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Profile'),
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Expression')
        );

        try {
            $tool->createSchema($classes);
        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            
        }
    }

    /**
     * Removes the plugin database tables
     */
    private function removeDatabaseTables()
    {
        $em = $this->Application()->Models();
        $tool = new \Doctrine\ORM\Tools\SchemaTool($em);

        $classes = array(
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Session'),
//            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Profile'),
//            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Expression')
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
                    'label' => 'Import/Export',
                    'controller' => 'SwagImportExport',
                    'class' => 'sprite-server--plus',
                    'action' => 'Index',
                    'active' => 1,
                    'parent' => $this->Menu()->findOneBy('label', 'Inhalte'),
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
                'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExport', 'getBackendController'
        );

        $this->subscribeEvent(
                'Enlight_Controller_Action_PostDispatch_Backend_Index', 'injectBackendAceEditor'
        );
        $this->subscribeEvent(
                'Shopware_Console_Add_Command', 'onAddConsoleCommand'
        );
    }

    /**
     * Returns the path to the backend controller.
     *
     * @param Enlight_Event_EventArgs $args
     * @return string
     */
    public function getBackendController(Enlight_Event_EventArgs $args)
    {
        $this->registerMyNamespace();

        $this->Application()->Snippets()->addConfigDir(
                $this->Path() . 'Snippets/'
        );

        $this->Application()->Template()->addTemplateDir(
                $this->Path() . 'Views/'
        );

        return $this->Path() . '/Controllers/Backend/SwagImportExport.php';
    }

    /**
     * Injects Ace Editor used in Conversions GUI
     * 
     * @param Enlight_Event_EventArgs $args
     */
    public function injectBackendAceEditor(Enlight_Event_EventArgs $args)
    {
        $controller = $args->getSubject();
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate()
        ) {
            return;
        }

        $view->addTemplateDir($this->Path() . 'Views/');
        $view->extendsTemplate('backend/swag_import_export/menu_entry.tpl');
    }

    /**
     * Adds the console commands (sw:import and sw:export)
     * 
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function onAddConsoleCommand(Enlight_Event_EventArgs $args)
    {
        $this->registerMyNamespace();
        return new Doctrine\Common\Collections\ArrayCollection(array(
            new \Shopware\Commands\SwagImportExport\ImportCommand(),
            new \Shopware\Commands\SwagImportExport\ExportCommand(),
            new \Shopware\Commands\SwagImportExport\ProfilesCommand(),
        ));
    }

    /**
     * Cronjob for import
     * 
     * @param Shopware_Components_Cron_CronJob $job
     * @return boolean
     */
    public function onRunImportCronJob(Shopware_Components_Cron_CronJob $job)
    {
        $this->registerMyNamespace();
        $files = scandir(Shopware()->DocPath() . 'files/import_cron/');
        
        if ($files === false) {
            return false;
        }
        
        $manager = Shopware()->Models();
        
        $profileRepository = $manager->getRepository('Shopware\CustomModels\ImportExport\Profile');
        foreach($files as $file) {
            $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
            if ($type == 'xml' || $type == 'csv') {
                try {
                    $profile = \Shopware\Components\SwagImportExport\Utils\CommandHelper::findProfileByName($file, $profileRepository);

                    if ($profile === false) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('cronjob/no_profile', 'Failed to create directory %s');
                        throw new \Exception(sprintf($message, $file));
                    }

                    $albumRepo = Shopware()->Models()->getRepository('Shopware\Models\Media\Album');
                    $album = $albumRepo->findOneBy(array('name' => 'ImportFiles'));

                    $filePath = Shopware()->DocPath() . 'files/import_cron/' . $file;
                    $fileObject = new \Symfony\Component\HttpFoundation\File\File($filePath);

                    if (!$album) {
                        $album = new Shopware\Models\Media\Album();
                        $album->setName('ImportFiles');
                        $album->setPosition(0);
                        Shopware()->Models()->persist($album);
                        Shopware()->Models()->flush($album);
                    }

                    $media = new \Shopware\Models\Media\Media();

                    $media->setAlbum($album);
                    $media->setDescription('');
                    $media->setCreated(new DateTime());
                    $media->setExtension($type);

                    $identity = Shopware()->Auth()->getIdentity();
                    if ($identity !== null) {
                        $media->setUserId($identity->id);
                    } else {
                        $media->setUserId(0);
                    }

                    //set the upload file into the model. The model saves the file to the directory
                    $media->setFile($fileObject);

                    Shopware()->Models()->persist($media);
                    Shopware()->Models()->flush();

                    $mediaPath = $media->getPath();

                    $commandHelper = new \Shopware\Components\SwagImportExport\Utils\CommandHelper(array(
                        'profileEntity' => $profile,
                        'filePath' => $mediaPath,
                        'format' => $type,
                    ));
                
                } catch (\Exception $e) {
                    echo $e->getMessage() . "\n";
                    return;
                }

                try {
                    $return = $commandHelper->prepareImport();
                    $count = $return['count'];

                    $return = $commandHelper->importAction();
                    $position = $return['data']['position'];

                    while ($position < $count) {
                        $return = $commandHelper->importAction();
                        $position = $return['data']['position'];
                    }
                    
                } catch (\Exception $e) {
                    // copy file as broken
                    copy($mediaPath, Shopware()->DocPath() . 'files/import_export/broken-' . $file);
                    echo $e->getMessage() . "\n";
                    return ;
                }
            }
        }
        
        return true;
    }

}
