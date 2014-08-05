<?php

namespace Tests\Shopware\ImportExport;

class ImportExportTestHelper extends \Enlight_Components_Test_Plugin_TestCase
{

    /**
     * @var Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    protected $plugin;

    /**
     * Test set up method
     */
    public function setUp()
    {
        parent::setUp();

        $this->plugin = Shopware()->Plugins()->Backend()->SwagImportExport();
    }

    /**
     * Retrieve plugin instance
     *
     * @return Shopware_Plugins_Frontend_Statistics_Bootstrap
     */
    public function Plugin()
    {
        return $this->plugin;
    }

}
