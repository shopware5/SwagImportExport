<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;
use SwagImportExport\Setup\DefaultProfiles\TranslationProfile;

class TranslationProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $translationProfile = new TranslationProfile();

        static::assertInstanceOf(TranslationProfile::class, $translationProfile);
        static::assertInstanceOf(\JsonSerializable::class, $translationProfile);
        static::assertInstanceOf(ProfileMetaData::class, $translationProfile);
    }

    public function testItShouldReturnValidProfile(): void
    {
        $translationProfile = new TranslationProfile();

        $this->walkRecursive($translationProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });
    }
}
