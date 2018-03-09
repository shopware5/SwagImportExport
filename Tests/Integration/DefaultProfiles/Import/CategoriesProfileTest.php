<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

/**
 * Covers the default profile:
 * default_categories
 */
class CategoriesProfileTest extends \PHPUnit\Framework\TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_create_category_with_imported_id()
    {
        $filePath = $this->getImportFile('categories_profile_test.csv');
        $this->runCommand("sw:import:import -p default_categories {$filePath}");

        $updateCategory = $this->executeQuery('SELECT * FROM s_categories WHERE id=3');
        $createdCategory = $this->executeQuery("SELECT * FROM s_categories WHERE description='NewCategoryWithId'");
        $createdChildCategory = $this->executeQuery("SELECT * FROM s_categories WHERE description='NewChildCategoryWithId'");

        //Assert updated category
        $this->assertEquals(3, $updateCategory[0]['id'], 'Could not find updated category');
        $this->assertEquals('Update', $updateCategory[0]['description'], 'Could not update descirption of a category.');
        $this->assertEquals(1000, $updateCategory[0]['position'], 'Could not update position');

        //Assertions for parent category
        $this->assertEquals('NewCategoryWithId', $createdCategory[0]['description']);
        $this->assertEquals(9999, $createdCategory[0]['id'], 'Category was not imported with given id from import file.');

        //Assertions for child category
        $this->assertEquals('NewChildCategoryWithId', $createdChildCategory[0]['description']);
        $this->assertEquals(10000, $createdChildCategory[0]['id'], 'Category was not imported with given id from import file.');
        $this->assertEquals(9999, $createdChildCategory[0]['parent'], 'Category was not imported with given parents from import file.');
    }
}
