<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class MinimalOrdersProfileTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_write_should_update_order_status()
    {
        $filePath = __DIR__ . "/_fixtures/minimal_orders_profile.csv";
        $expectedOrderId = 15;
        $expectedOrderStatus = 1;

        $this->runCommand("sw:import:import -p default_orders_minimal {$filePath}");

        $updatedOrder = $this->executeQuery("SELECT * FROM s_order WHERE id='{$expectedOrderId}'");

        $this->assertEquals($expectedOrderStatus, $updatedOrder[0]["status"]);
    }

    /*public function test_write_with_invalid_order_status_throws_exception()
    {
        $filePath = __DIR__ . "/_fixtures/minimal_orders_profile.csv";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("status Feld muss int sein!");
        $this->runCommand("sw:import:import -p default_orders_minimal {$filePath}");
    }*/
}