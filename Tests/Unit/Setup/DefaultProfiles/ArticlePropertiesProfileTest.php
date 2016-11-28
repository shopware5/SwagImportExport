<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use Shopware\Setup\SwagImportExport\DefaultProfiles\ArticlePropertiesProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class ArticlePropertiesProfileTest extends \PHPUnit_Framework_TestCase
{
    use DefaultProfileTestCaseTrait;

    /**
     * @return ArticlePropertiesProfile
     */
    private function createArticlePropertiesProfile()
    {
        return new ArticlePropertiesProfile();
    }

    public function test_it_can_be_created()
    {
        $articleProfile = $this->createArticlePropertiesProfile();

        $this->assertInstanceOf(ArticlePropertiesProfile::class, $articleProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $articleProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $articleProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $articleProfile = $this->createArticlePropertiesProfile();

        $this->walkRecursive($articleProfile->jsonSerialize(), function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . print_r($node, true));
        });
    }
}