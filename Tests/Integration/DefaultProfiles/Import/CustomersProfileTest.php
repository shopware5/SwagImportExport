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
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class CustomersProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldUpdateCustomerShippingAddress(): void
    {
        $filePath = __DIR__ . '/_fixtures/customers_profile.csv';
        $expectedCity = 'New Shipping City';
        $expectedZip = 12345;
        $expectedBigDataCustomerEmail = 'mustermann@b2b73.de';

        $this->runCommand("sw:import:import -p default_customers {$filePath}");

        $updatedCustomer = $this->executeQuery("SELECT * FROM s_user as u JOIN s_user_addresses AS s ON u.id = s.user_id WHERE u.customernumber = '20003'");
        $bigDataTestCustomer = $this->executeQuery("SELECT * FROM s_user WHERE customernumber = '20075'");

        static::assertEquals($expectedZip, $updatedCustomer[0]['zipcode']);
        static::assertEquals($expectedCity, $updatedCustomer[1]['city']);
        static::assertEquals($expectedBigDataCustomerEmail, $bigDataTestCustomer[0]['email']);
    }
}
