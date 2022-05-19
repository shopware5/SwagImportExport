<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Setup\DefaultProfiles\ArticlePriceProfile;

class ArticlePriceProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItShouldReturnValidProfileTree(): void
    {
        $articleProfile = $this->createArticlePriceProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    private function createArticlePriceProfile(): ArticlePriceProfile
    {
        return new ArticlePriceProfile();
    }
}
