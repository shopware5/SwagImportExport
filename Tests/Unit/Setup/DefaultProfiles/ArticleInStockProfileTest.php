<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleInStockProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class ArticleInStockProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    /**
     * @return ArticleInStockProfile
     */
    public function createArticleInStockProfile()
    {
        return new ArticleInStockProfile();
    }

    public function test_it_can_be_created()
    {
        $articleInStockProfile = $this->createArticleInStockProfile();

        static::assertInstanceOf(ArticleInStockProfile::class, $articleInStockProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleInStockProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleInStockProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleProfile = $this->createArticleInStockProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
        });
    }
}
