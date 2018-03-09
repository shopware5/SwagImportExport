<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class MinimalCategoriesProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_import_categories()
    {
        $filePath = $this->getImportFile('categories_profile_test.csv');
        $this->runCommand("sw:import:import -p default_categories {$filePath}");

        $createdCategory = $this->executeQuery("SELECT * FROM s_categories WHERE description='NewCategoryWithId'");
        $createdChildCategory = $this->executeQuery("SELECT * FROM s_categories WHERE description='NewChildCategoryWithId'");

        //Parent category assertion
        $this->assertEquals('NewCategoryWithId', $createdCategory[0]['description']);
        $this->assertEquals('9999', $createdCategory[0]['id']);

        //Child category assertion
        $this->assertEquals('NewChildCategoryWithId', $createdChildCategory[0]['description']);
        $this->assertEquals('10000', $createdChildCategory[0]['id']);
        $this->assertEquals('9999', $createdChildCategory[0]['parent']);
    }
}
