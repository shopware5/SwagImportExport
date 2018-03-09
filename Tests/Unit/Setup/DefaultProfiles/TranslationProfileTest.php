<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;
use Shopware\Setup\SwagImportExport\DefaultProfiles\TranslationProfile;

class TranslationProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $translationProfile = new TranslationProfile();

        $this->assertInstanceOf(TranslationProfile::class, $translationProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $translationProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $translationProfile);
    }

    public function test_it_should_return_valid_profile()
    {
        $translationProfile = new TranslationProfile();

        $this->walkRecursive($translationProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
        });
    }
}
