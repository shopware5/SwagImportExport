<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleImagesProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class ArticleImagesProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $articleImagesProfile = $this->createArticleImagesProfile();

        static::assertInstanceOf(ArticleImagesProfile::class, $articleImagesProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleImagesProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleImagesProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleAllProfile = $this->createArticleImagesProfile();

        $this->walkRecursive($articleAllProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    /**
     * @return ArticleImagesProfile
     */
    private function createArticleImagesProfile()
    {
        return new ArticleImagesProfile();
    }
}
