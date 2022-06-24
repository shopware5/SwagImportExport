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
use SwagImportExport\Setup\DefaultProfiles\ProductImagesProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductImagesProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productImagesProfile = $this->createProductImagesProfile();

        static::assertInstanceOf(ProductImagesProfile::class, $productImagesProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productImagesProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productImagesProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productImagesProfile = $this->createProductImagesProfile();

        $this->walkRecursive($productImagesProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductImagesProfile(): ProductImagesProfile
    {
        return new ProductImagesProfile();
    }
}
