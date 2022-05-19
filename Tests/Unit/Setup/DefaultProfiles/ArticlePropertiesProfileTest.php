<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticlePropertiesProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticlePropertiesProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated()
    {
        $articleProfile = $this->createArticlePropertiesProfile();

        static::assertInstanceOf(ArticlePropertiesProfile::class, $articleProfile);
        static::assertInstanceOf(ProfileMetaData::class, $articleProfile);
        static::assertInstanceOf(\JsonSerializable::class, $articleProfile);
    }

    public function testItShouldReturnValidProfileTree()
    {
        $articleProfile = $this->createArticlePropertiesProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    /**
     * @return ArticlePropertiesProfile
     */
    private function createArticlePropertiesProfile()
    {
        return new ArticlePropertiesProfile();
    }
}
