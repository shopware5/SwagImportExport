<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use Doctrine\DBAL\Connection;

class ImportExportTestHelper extends \Enlight_Components_Test_Plugin_TestCase
{
    /**
     * @var \Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    protected $plugin;

    /**
     * Test set up method
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->beginTransaction();

        $this->plugin = Shopware()->Plugins()->Backend()->SwagImportExport();
    }

    protected function tearDown(): void
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->rollBack();
        parent::tearDown();
    }

    /**
     * Retrieve plugin instance
     *
     * @return \Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    public function Plugin()
    {
        return $this->plugin;
    }
}
