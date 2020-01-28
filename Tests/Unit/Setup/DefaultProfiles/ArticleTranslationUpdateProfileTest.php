<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleTranslationUpdateProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class ArticleTranslationUpdateProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $articleTranslationUpdateProfile = $this->createArticleTranslationUpdateProfile();

        static::assertInstanceOf(ArticleTranslationUpdateProfile::class, $articleTranslationUpdateProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleTranslationUpdateProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleTranslationUpdateProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleTranslationUpdateProfile = $this->createArticleTranslationUpdateProfile();

        $this->walkRecursive($articleTranslationUpdateProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
        });
    }

    /**
     * @return ArticleTranslationUpdateProfile
     */
    private function createArticleTranslationUpdateProfile()
    {
        return new ArticleTranslationUpdateProfile();
    }
}
