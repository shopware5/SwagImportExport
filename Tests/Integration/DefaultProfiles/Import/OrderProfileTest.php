<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class OrderProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;

    public function test_write_should_update_order_status()
    {
        $filePath = __DIR__ . '/_fixtures/order_profile.csv';
        $expectedOrderId = 15;
        $expectedOrderStatus = 1;

        $this->runCommand("sw:import:import -p default_orders {$filePath}");

        $updatedOrder = $this->executeQuery("SELECT * FROM s_order WHERE id='{$expectedOrderId}'");

        static::assertEquals($expectedOrderStatus, $updatedOrder[0]['status']);
    }
}
