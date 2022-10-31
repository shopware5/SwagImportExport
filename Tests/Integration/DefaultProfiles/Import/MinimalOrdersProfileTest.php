<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class MinimalOrdersProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testWriteShouldUpdateOrderStatus(): void
    {
        $filePath = __DIR__ . '/_fixtures/minimal_orders_profile.csv';
        $expectedOrderId = 15;
        $expectedOrderStatus = 1;

        $this->runCommand("sw:import:import -p default_orders_minimal {$filePath}");

        $updatedOrder = $this->executeQuery("SELECT * FROM s_order WHERE id='{$expectedOrderId}'");

        static::assertEquals($expectedOrderStatus, $updatedOrder[0]['status']);
    }
}
