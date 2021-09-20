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

class ArticleInStockProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;

    public function testWriteShouldUpdateArticleStock()
    {
        $filePath = __DIR__ . '/_fixtures/article_in_stock_profile.csv';
        $expectedArticleOrderNumber = 'SW10003';
        $expectedArticleStock = 47;

        $this->runCommand("sw:import:import -p default_article_in_stock {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedArticleOrderNumber}'");

        static::assertEquals($expectedArticleStock, $updatedArticle[0]['instock']);
    }
}
