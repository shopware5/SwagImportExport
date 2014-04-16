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
        return 'Swag Import/Export';
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

        return true;
    }

    /**
     * Uninstall function of the plugin.
     * Fired from the plugin manager.
     * @return bool
     */
    public function uninstall()
    {
//        $this->removeDatabaseTables();

        return true;
    }

    /**
     * Register components directory
     */
    public function registerMyNamespace()
    {
        $this->Application()->Loader()->registerNamespace(
                'Shopware\Components', $this->Path() . 'Components/'
        );
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
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Profile')
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
            $em->getClassMetadata('Shopware\CustomModels\ImportExport\Profile')
        );

        $tool->dropSchema($classes);
    }

}
