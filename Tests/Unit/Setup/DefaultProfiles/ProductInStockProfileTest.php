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
use SwagImportExport\Setup\DefaultProfiles\ProductInStockProfile;

class ProductInStockProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItShouldReturnValidProfileTree(): void
    {
        $productProfile = $this->createProductInStockProfile();

        $this->walkRecursive($productProfile->jsonSerialize(), function ($node): void {
            static::assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            static::assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            static::assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createProductInStockProfile(): ProductInStockProfile
    {
        return new ProductInStockProfile();
    }
}
