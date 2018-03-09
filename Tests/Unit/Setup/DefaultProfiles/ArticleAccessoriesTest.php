<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleAccessoryProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class ArticleAccessoriesTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $articleAccessoryProfile = $this->createArticleAccessoryProfile();

        $this->assertInstanceOf(ArticleAccessoryProfile::class, $articleAccessoryProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $articleAccessoryProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $articleAccessoryProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleAccessoryProfile = $this->createArticleAccessoryProfile();

        $this->walkRecursive($articleAccessoryProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
        });
    }

    /**
     * @return ArticleAccessoryProfile
     */
    private function createArticleAccessoryProfile()
    {
        return new ArticleAccessoryProfile();
    }
}
