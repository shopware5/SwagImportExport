<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\AddressProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;
use SwagImportExport\Tests\Unit\Setup\DefaultProfiles\DefaultProfileTestCaseTrait;

class AddressProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $addressProfile = new AddressProfile();
        static::assertInstanceOf(AddressProfile::class, $addressProfile);
        static::assertInstanceOf(ProfileMetaData::class, $addressProfile);
        static::assertInstanceOf(\JsonSerializable::class, $addressProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $addressProfile = new AddressProfile();

        $profileTree = $addressProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($addressProfile);
        static::assertJson($profileJson);
    }
}
