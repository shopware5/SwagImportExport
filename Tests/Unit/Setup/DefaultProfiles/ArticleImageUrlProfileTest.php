<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticleImageUrlProfile;

class ArticleImageUrlProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function test_it_should_return_valid_profile_tree()
    {
        $articleImageUrlProfile = $this->createArticleImageUrlProfile();

        $this->walkRecursive($articleImageUrlProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });
    }

    /**
     * @return ArticleImageUrlProfile
     */
    private function createArticleImageUrlProfile()
    {
        return new ArticleImageUrlProfile();
    }
}
