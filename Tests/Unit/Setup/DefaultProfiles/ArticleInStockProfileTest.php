<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticleInStockProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleInStockProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function createArticleInStockProfile(): ArticleInStockProfile
    {
        return new ArticleInStockProfile();
    }

    public function testItCanBeCreated(): void
    {
        $articleInStockProfile = $this->createArticleInStockProfile();

        static::assertInstanceOf(ArticleInStockProfile::class, $articleInStockProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleInStockProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleInStockProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $articleProfile = $this->createArticleInStockProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }
}
