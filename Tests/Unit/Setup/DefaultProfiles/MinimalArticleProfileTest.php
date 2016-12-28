<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use Shopware\Setup\SwagImportExport\DefaultProfiles\MinimalArticleProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class MinimalArticleProfileTest extends \PHPUnit_Framework_TestCase
{
    use DefaultProfileTestCaseTrait;

    /**
     * @return MinimalArticleProfile
     */
    private function getMinimalArticleProfile()
    {
        return new MinimalArticleProfile();
    }

    public function test_it_can_be_created()
    {
        $minimalArticleProfile = $this->getMinimalArticleProfile();

        $this->assertInstanceOf(MinimalArticleProfile::class, $minimalArticleProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $minimalArticleProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $minimalArticleProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $minimalArticleProfile = $this->getMinimalArticleProfile();

        $profileTree = $minimalArticleProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node) {
            $this->assertArrayHasKey('id', $node, "Current array: " . print_r($node, true));
            $this->assertArrayHasKey('type', $node, "Current array: " . print_r($node, true));
            $this->assertArrayHasKey('name', $node, "Current array: " . print_r($node, true));
        });

        $profileJson = json_encode($minimalArticleProfile);
        $this->assertJson($profileJson);
    }
}
