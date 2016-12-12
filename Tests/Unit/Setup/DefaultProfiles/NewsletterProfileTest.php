<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use Shopware\Setup\SwagImportExport\DefaultProfiles\NewsletterRecipientProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class NewsletterProfileTest extends \PHPUnit_Framework_TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $newsletterProfile = new NewsletterRecipientProfile();

        $this->assertInstanceOf(NewsletterRecipientProfile::class, $newsletterProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $newsletterProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $newsletterProfile);
    }

    public function test_it_should_return_valid_profile()
    {
        $newsletterProfile = new NewsletterRecipientProfile();

        $this->walkRecursive($newsletterProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, "Current array: " . print_r($node, true));
            $this->assertArrayHasKey('type', $node, "Current array: " . print_r($node, true));
            $this->assertArrayHasKey('name', $node, "Current array: " . print_r($node, true));
        });
    }
}
