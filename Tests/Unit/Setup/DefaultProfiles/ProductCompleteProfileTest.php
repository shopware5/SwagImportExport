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
use SwagImportExport\Setup\DefaultProfiles\ProductCompleteProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductCompleteProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productProfile = new ProductCompleteProfile();

        static::assertInstanceOf(ProductCompleteProfile::class, $productProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productAllProfile = $this->createProductAllProfile();

        $this->walkRecursive($productAllProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductAllProfile(): ProductCompleteProfile
    {
        return new ProductCompleteProfile();
    }
}
