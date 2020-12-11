<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\MinimalArticleVariantsProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class MinimalArticleVariantsProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $articleVariantsMinimalProfile = $this->createMinimalArticleVariantsProfile();

        static::assertInstanceOf(MinimalArticleVariantsProfile::class, $articleVariantsMinimalProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleVariantsMinimalProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleVariantsMinimalProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleProfile = $this->createMinimalArticleVariantsProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    /**
     * @return MinimalArticleVariantsProfile
     */
    private function createMinimalArticleVariantsProfile()
    {
        return new MinimalArticleVariantsProfile();
    }
}
