<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticleImagesProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleImagesProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $articleImagesProfile = $this->createArticleImagesProfile();

        static::assertInstanceOf(ArticleImagesProfile::class, $articleImagesProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleImagesProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleImagesProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $articleAllProfile = $this->createArticleImagesProfile();

        $this->walkRecursive($articleAllProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createArticleImagesProfile(): ArticleImagesProfile
    {
        return new ArticleImagesProfile();
    }
}
