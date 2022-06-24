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
use SwagImportExport\Setup\DefaultProfiles\ProductSimilarsProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductSimilarsProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productSimilarsProfile = $this->createProductSimilarsProfile();

        static::assertInstanceOf(ProductSimilarsProfile::class, $productSimilarsProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productSimilarsProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productSimilarsProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productSimilarsProfile = $this->createProductSimilarsProfile();

        $this->walkRecursive($productSimilarsProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductSimilarsProfile(): ProductSimilarsProfile
    {
        return new ProductSimilarsProfile();
    }
}
