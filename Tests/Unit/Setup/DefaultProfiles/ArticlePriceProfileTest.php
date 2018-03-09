<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticlePriceProfile;

class ArticlePriceProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_should_return_valid_profile_tree()
    {
        $articleProfile = $this->createArticlePriceProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
        });
    }

    /**
     * @return ArticlePriceProfile
     */
    private function createArticlePriceProfile()
    {
        return new ArticlePriceProfile();
    }
}
