<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\MinimalProductProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class MinimalProductProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $minimalProductProfile = $this->getMinimalProductProfile();

        static::assertInstanceOf(MinimalProductProfile::class, $minimalProductProfile);
        static::assertInstanceOf(\JsonSerializable::class, $minimalProductProfile);
        static::assertInstanceOf(ProfileMetaData::class, $minimalProductProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $minimalProductProfile = $this->getMinimalProductProfile();

        $profileTree = $minimalProductProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($minimalProductProfile);
        static::assertJson($profileJson);
    }

    private function getMinimalProductProfile(): MinimalProductProfile
    {
        return new MinimalProductProfile();
    }
}
