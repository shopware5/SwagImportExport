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
use SwagImportExport\Setup\DefaultProfiles\ProductInStockProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductInStockProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function createProductInStockProfile(): ProductInStockProfile
    {
        return new ProductInStockProfile();
    }

    public function testItCanBeCreated(): void
    {
        $productInStockProfile = $this->createProductInStockProfile();

        static::assertInstanceOf(ProductInStockProfile::class, $productInStockProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productInStockProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productInStockProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productProfile = $this->createProductInStockProfile();

        $this->walkRecursive($productProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }
}
