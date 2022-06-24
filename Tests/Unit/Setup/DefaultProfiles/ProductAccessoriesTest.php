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
use SwagImportExport\Setup\DefaultProfiles\ProductAccessoryProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductAccessoriesTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productAccessoryProfile = $this->createProductAccessoryProfile();

        static::assertInstanceOf(ProductAccessoryProfile::class, $productAccessoryProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productAccessoryProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productAccessoryProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productAccessoryProfile = $this->createProductAccessoryProfile();

        $this->walkRecursive($productAccessoryProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductAccessoryProfile(): ProductAccessoryProfile
    {
        return new ProductAccessoryProfile();
    }
}
