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

class ProductCategoriesProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testShouldUpdateExistingProductCategories(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_categories_profile.csv';
        $expectedOrderNumber = 'SW10002.3';
        $expectedCategoryId = 35;
        $expectedCategoryArrayLength = 6;

        $this->runCommand("sw:import:import -p default_article_categories {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedOrderNumber}'");
        $updatedProductCategories = $this->executeQuery("SELECT * FROM s_articles_categories WHERE articleID='{$updatedProduct[0]['articleID']}'");

        static::assertEquals($expectedCategoryId, $updatedProductCategories[3]['categoryID']);
        static::assertCount($expectedCategoryArrayLength, $updatedProductCategories);
    }
}
