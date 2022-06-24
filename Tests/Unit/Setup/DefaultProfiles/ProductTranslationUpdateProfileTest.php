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
use SwagImportExport\Setup\DefaultProfiles\ProductTranslationUpdateProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class ProductTranslationUpdateProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $productTranslationUpdateProfile = $this->createProductTranslationUpdateProfile();

        static::assertInstanceOf(ProductTranslationUpdateProfile::class, $productTranslationUpdateProfile);
        static::assertInstanceOf(\JsonSerializable::class, $productTranslationUpdateProfile);
        static::assertInstanceOf(ProfileMetaData::class, $productTranslationUpdateProfile);
    }

    public function testItShouldReturnValidProfileTree(): void
    {
        $productTranslationUpdateProfile = $this->createProductTranslationUpdateProfile();

        $this->walkRecursive($productTranslationUpdateProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductTranslationUpdateProfile(): ProductTranslationUpdateProfile
    {
        return new ProductTranslationUpdateProfile();
    }
}
