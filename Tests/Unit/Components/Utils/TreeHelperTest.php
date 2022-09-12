<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Components\Utils;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Utils\TreeHelper;
use SwagImportExport\Setup\DefaultProfiles\CustomerProfile;

class TreeHelperTest extends TestCase
{
    public function testGetNodeById(): void
    {
        $expectedNodeId = '540d9e8c6ab4f';
        $tree = (new CustomerProfile())->jsonSerialize();
        $node = TreeHelper::getNodeById($expectedNodeId, $tree);
        static::assertIsArray($node);
        static::assertSame($expectedNodeId, $node['id']);
        static::assertSame('active', $node['name']);
        static::assertSame('leaf', $node['type']);
    }
}
