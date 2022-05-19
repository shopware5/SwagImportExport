<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\MinimalOrdersProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class MinimalOrdersProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated()
    {
        $minimalOrdersProfile = $this->createMinimalOrdersProfile();

        static::assertInstanceOf(MinimalOrdersProfile::class, $minimalOrdersProfile);
        static::assertInstanceOf(\JsonSerializable::class, $minimalOrdersProfile);
        static::assertInstanceOf(ProfileMetaData::class, $minimalOrdersProfile);
    }

    public function testItShouldReturnValidProfileTree()
    {
        $minimalOrdersProfile = $this->createMinimalOrdersProfile();

        $profileTree = $minimalOrdersProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    /**
     * @return MinimalOrdersProfile
     */
    private function createMinimalOrdersProfile()
    {
        return new MinimalOrdersProfile();
    }
}
