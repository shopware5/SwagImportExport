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

class AddressProfileTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_should_import_new_addresses()
    {
        $file = __DIR__ . '/_fixtures/addresses_profile_create.csv';
        $this->runCommand("sw:importexport:import -p default_addresses {$file}");

        $createdAddress = $this->executeQuery('SELECT * FROM s_user_addresses WHERE company="A Company Ltd."');

        $this->assertEquals('Department X', $createdAddress[0]['department']);
        $this->assertEquals('MyUstId', $createdAddress[0]['ustid']);
        $this->assertEquals('My City', $createdAddress[0]['city']);
        $this->assertEquals('Firstname', $createdAddress[0]['firstname']);
        $this->assertEquals('Lastname', $createdAddress[0]['lastname']);
        $this->assertEquals('ms', $createdAddress[0]['salutation']);
        $this->assertEquals('My phonenumber', $createdAddress[0]['phone']);
        $this->assertEquals('My additional address', $createdAddress[0]['additional_address_line1']);
    }

    public function test_should_update_existing_addresses_by_id()
    {
        $file = __DIR__ . '/_fixtures/addresses_profile_update.csv';
        $this->runCommand("sw:importexport:import -p default_addresses {$file}");

        $updatedAddress = $this->executeQuery('SELECT * FROM s_user_addresses WHERE company="Updated company"');

        $this->assertEquals(2, $updatedAddress[0]['id']);
        $this->assertEquals('Updated department', $updatedAddress[0]['department']);
        $this->assertEquals('Updated firstname', $updatedAddress[0]['firstname']);
        $this->assertEquals('Updated lastname', $updatedAddress[0]['lastname']);
        $this->assertEquals('Updated city', $updatedAddress[0]['city']);
        $this->assertEquals('Updated vatid', $updatedAddress[0]['ustid']);
        $this->assertEquals('Updated salutation', $updatedAddress[0]['salutation']);
        $this->assertEquals('Updated telephone', $updatedAddress[0]['phone']);
        $this->assertEquals('Updated additional address', $updatedAddress[0]['additional_address_line1']);
    }
}
