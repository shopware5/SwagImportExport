<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticleAccessoryProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleAccessoriesTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated()
    {
        $articleAccessoryProfile = $this->createArticleAccessoryProfile();

        static::assertInstanceOf(ArticleAccessoryProfile::class, $articleAccessoryProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleAccessoryProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleAccessoryProfile);
    }

    public function testItShouldReturnValidProfileTree()
    {
        $articleAccessoryProfile = $this->createArticleAccessoryProfile();

        $this->walkRecursive($articleAccessoryProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
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
