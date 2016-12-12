<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use Shopware\Setup\SwagImportExport\DefaultProfiles\CategoryProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class CategoryProfileTest extends \PHPUnit_Framework_TestCase
{
    use DefaultProfileTestCaseTrait;

    /**
     * @return CategoryProfile
     */
    private function createCategoryProfile()
    {
        return new CategoryProfile();
    }

    public function test_it_can_be_created()
    {
        $categoryProfile = $this->createCategoryProfile();

        $this->assertInstanceOf(CategoryProfile::class, $categoryProfile);
        $this->assertInstanceOf(\JsonSerializable::class, $categoryProfile);
        $this->assertInstanceOf(ProfileMetaData::class, $categoryProfile);
    }

    public function test_it_should_return_valid_profile_tree()
    {
        $categoryProfile = $this->createCategoryProfile();

        $profileTree = $categoryProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node) {
            $this->assertArrayHasKey('id', $node, "Current array: " . print_r($node, true));
            $this->assertArrayHasKey('name', $node, "Current array: " . print_r($node, true));
            $this->assertArrayHasKey('type', $node, "Current array: " . print_r($node, true));
        });

        $profileJson = json_encode($categoryProfile->jsonSerialize());
        $this->assertJson($profileJson);
    }
}
