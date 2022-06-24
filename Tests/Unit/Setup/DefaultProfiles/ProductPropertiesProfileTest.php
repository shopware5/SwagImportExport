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
use SwagImportExport\Setup\DefaultProfiles\ProductPropertiesProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductPropertiesProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productPropertiesProfile = $this->createProductPropertiesProfile();

        static::assertInstanceOf(ProductPropertiesProfile::class, $productPropertiesProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productPropertiesProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productPropertiesProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productPropertiesProfile = $this->createProductPropertiesProfile();

        $this->walkRecursive($productPropertiesProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductPropertiesProfile(): ProductPropertiesProfile
    {
        return new ProductPropertiesProfile();
    }
}
