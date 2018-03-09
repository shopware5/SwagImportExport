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

class ArticleCategoriesProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_should_update_existing_article_categories()
    {
        $filePath = __DIR__ . '/_fixtures/article_categories_profile.csv';
        $expectedOrderNumber = 'SW10002.3';
        $expectedCategoryId = 35;
        $expectedCategoryArrayLength = 6;

        $this->runCommand("sw:import:import -p default_article_categories {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedOrderNumber}'");
        $updatedArticleCategories = $this->executeQuery("SELECT * FROM s_articles_categories WHERE articleID='{$updatedArticle[0]['articleID']}'");

        $this->assertEquals($expectedCategoryId, $updatedArticleCategories[3]['categoryID']);
        $this->assertCount($expectedCategoryArrayLength, $updatedArticleCategories);
    }
}
