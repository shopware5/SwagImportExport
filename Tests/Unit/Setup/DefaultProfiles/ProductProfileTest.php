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
use SwagImportExport\Setup\DefaultProfiles\ProductProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productProfile = $this->createProductProfile();

        static::assertInstanceOf(ProductProfile::class, $productProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productProfile = $this->createProductProfile();

        $this->walkRecursive($productProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductProfile(): ProductProfile
    {
        return new ProductProfile();
    }
}
