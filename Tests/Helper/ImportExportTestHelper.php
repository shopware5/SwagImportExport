<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Doctrine\DBAL\Connection;
use Shopware\Components\DependencyInjection\Container;

abstract class ImportExportTestHelper extends \Enlight_Components_Test_Plugin_TestCase
{
    /**
     * Test set up method
     */
    public function setUp(): void
    {
        parent::setUp();

        /** @var Connection $connection */
        $connection = $this->getContainer()->get('dbal_connection');
        $connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('dbal_connection');
        $connection->rollBack();
        parent::tearDown();
    }

    abstract public function getContainer(): Container;
}
