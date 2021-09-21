<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\DefaultProfiles;

use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\DefaultProfiles\CategoryProfile;
use Shopware\Setup\SwagImportExport\DefaultProfiles\ProfileMetaData;

class CategoryProfileTest extends TestCase
{
    use DefaultProfileTestCaseTrait;

    public function testItCanBeCreated()
    {
        $categoryProfile = $this->createCategoryProfile();

        static::assertInstanceOf(CategoryProfile::class, $categoryProfile);
        static::assertInstanceOf(\JsonSerializable::class, $categoryProfile);
        static::assertInstanceOf(ProfileMetaData::class, $categoryProfile);
    }

    public function testItShouldReturnValidProfileTree()
    {
        $categoryProfile = $this->createCategoryProfile();

        $profileTree = $categoryProfile->jsonSerialize();
        $this->walkRecursive($profileTree, function ($node) {
            $this->assertArrayHasKey('id', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('name', $node, 'Current array: ' . \print_r($node, true));
            $this->assertArrayHasKey('type', $node, 'Current array: ' . \print_r($node, true));
        });

        $profileJson = \json_encode($categoryProfile);
        static::assertJson($profileJson);
    }

    /**
     * @return CategoryProfile
     */
    private function createCategoryProfile()
    {
        return new CategoryProfile();
    }
}
