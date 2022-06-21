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
use SwagImportExport\Setup\DefaultProfiles\OrderMainDataProfile;
use SwagImportExport\Setup\DefaultProfiles\ProfileMetaData;

class OrderMainDataProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated(): void
    {
        $orderMainDataProfile = new OrderMainDataProfile();

        static::assertInstanceOf(OrderMainDataProfile::class, $orderMainDataProfile);
        static::assertInstanceOf(ProfileMetaData::class, $orderMainDataProfile);
        static::assertInstanceOf(\JsonSerializable::class, $orderMainDataProfile);
    }

    public function testItShouldReturnValidProfile(): void
    {
        $orderMainDataProfile = new OrderMainDataProfile();

        $this->walkRecursive($orderMainDataProfile->jsonSerialize(), function ($node): void {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
        });
    }
}
