<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\MinimalOrdersProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class MinimalOrdersProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $minimalOrdersProfile = $this->createMinimalOrdersProfile();

        static::assertInstanceOf(MinimalOrdersProfile::class, $minimalOrdersProfile);
        static::assertInstanceOf(\JsonSerializable::class, $minimalOrdersProfile);
        static::assertInstanceOf(ProfileMetaData::class, $minimalOrdersProfile);
    }

    public function test_it_should_return_valid_profile_tree()
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
