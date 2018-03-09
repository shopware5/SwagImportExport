<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\OrderMainDataProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class OrderMainDataProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_can_be_created()
    {
        $orderMainDataProfile = new OrderMainDataProfile();

        $this->assertInstanceOf(OrderMainDataProfile::class, $orderMainDataProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $orderMainDataProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $orderMainDataProfile);
    }

    public function test_it_should_return_valid_profile()
    {
        $orderMainDataProfile = new OrderMainDataProfile();

        $this->walkRecursive($orderMainDataProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
        });
    }
}
