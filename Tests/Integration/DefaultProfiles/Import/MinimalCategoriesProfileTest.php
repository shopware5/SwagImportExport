<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class MinimalCategoriesProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testImportShouldImportCategories(): void
    {
        $filePath = $this->getImportFile('categories_profile_test.csv');
        $this->runCommand("sw:import:import -p default_categories {$filePath}");

        $createdCategory = $this->executeQuery("SELECT * FROM s_categories WHERE description='NewCategoryWithId'");
        $createdChildCategory = $this->executeQuery("SELECT * FROM s_categories WHERE description='NewChildCategoryWithId'");

        // Parent category assertion
        static::assertEquals('NewCategoryWithId', $createdCategory[0]['description']);
        static::assertEquals('9999', $createdCategory[0]['id']);

        // Child category assertion
        static::assertEquals('NewChildCategoryWithId', $createdChildCategory[0]['description']);
        static::assertEquals('10000', $createdChildCategory[0]['id']);
        static::assertEquals('9999', $createdChildCategory[0]['parent']);
    }
}
