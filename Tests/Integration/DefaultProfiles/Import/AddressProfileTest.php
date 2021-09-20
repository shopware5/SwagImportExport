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

class AddressProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;

    public function testShouldImportNewAddresses()
    {
        $file = __DIR__ . '/_fixtures/addresses_profile_create.csv';
        $this->runCommand("sw:importexport:import -p default_addresses {$file}");

        $createdAddress = $this->executeQuery("SELECT * FROM s_user_addresses WHERE company='A Company Ltd.'");

        static::assertEquals('Department X', $createdAddress[0]['department']);
        static::assertEquals('MyUstId', $createdAddress[0]['ustid']);
        static::assertEquals('My City', $createdAddress[0]['city']);
        static::assertEquals('Firstname', $createdAddress[0]['firstname']);
        static::assertEquals('Lastname', $createdAddress[0]['lastname']);
        static::assertEquals('ms', $createdAddress[0]['salutation']);
        static::assertEquals('My phonenumber', $createdAddress[0]['phone']);
        static::assertEquals('My additional address', $createdAddress[0]['additional_address_line1']);
    }

    public function testShouldUpdateExistingAddressesById()
    {
        $file = __DIR__ . '/_fixtures/addresses_profile_update.csv';
        $this->runCommand("sw:importexport:import -p default_addresses {$file}");

        $updatedAddress = $this->executeQuery("SELECT * FROM s_user_addresses WHERE company='Updated company'");

        static::assertEquals(2, $updatedAddress[0]['id']);
        static::assertEquals('Updated department', $updatedAddress[0]['department']);
        static::assertEquals('Updated firstname', $updatedAddress[0]['firstname']);
        static::assertEquals('Updated lastname', $updatedAddress[0]['lastname']);
        static::assertEquals('Updated city', $updatedAddress[0]['city']);
        static::assertEquals('Updated vatid', $updatedAddress[0]['ustid']);
        static::assertEquals('Updated salutation', $updatedAddress[0]['salutation']);
        static::assertEquals('Updated telephone', $updatedAddress[0]['phone']);
        static::assertEquals('Updated additional address', $updatedAddress[0]['additional_address_line1']);
    }
}
