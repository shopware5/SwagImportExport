<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticleCategoriesProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ArticleCategoriesProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $categoryProfile = $this->createArticleCategoriesProfile();

        static::assertInstanceOf(ArticleCategoriesProfile::class, $categoryProfile);
        static::assertInstanceOf(\JsonSerializable::class, $categoryProfile);
        static::assertInstanceOf(ProfileMetaData::class, $categoryProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $categoryProfile = $this->createArticleCategoriesProfile();

        $profileTree = $categoryProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($categoryProfile);
        static::assertJson($profileJson);
    }

    private function createArticleCategoriesProfile(): ArticleCategoriesProfile
    {
        return new ArticleCategoriesProfile();
    }
}
