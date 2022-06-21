<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\MinimalCategoryProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class MinimalCategoryProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $categoryMinimalProfile = $this->createMinimalCategoryProfile();

        static::assertInstanceOf(MinimalCategoryProfile::class, $categoryMinimalProfile);
        static::assertInstanceOf(\JsonSerializable::class, $categoryMinimalProfile);
        static::assertInstanceOf(ProfileMetaData::class, $categoryMinimalProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $minimalCategoryProfile = $this->createMinimalCategoryProfile();

        $profileTree = $minimalCategoryProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($minimalCategoryProfile);
        static::assertJson($profileJson);
    }

    private function createMinimalCategoryProfile(): MinimalCategoryProfile
    {
        return new MinimalCategoryProfile();
    }
}
