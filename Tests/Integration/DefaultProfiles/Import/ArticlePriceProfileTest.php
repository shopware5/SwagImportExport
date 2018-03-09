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

class ArticlePriceProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_update_article_price()
    {
        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdArticleOrderNumber = 'SW10003';
        $expectedPurchasePrice = 7.95;
        $expectedArticlePrice = 8.3613445378151;

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticlePrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedArticle[0]['articleID']}'");

        $this->assertEquals($expectedPurchasePrice, $updatedArticle[0]['purchaseprice']);
        $this->assertEquals($expectedArticlePrice, $updatedArticlePrice[0]['price']);
    }

    public function test_import_should_update_article_pseudo_price()
    {
        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdArticleOrderNumber = 'SW10003';
        $expectedArticlePseudoPrice = 13.403361344538;

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticlePseudoPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedArticle[0]['articleID']}'");

        $this->assertEquals($expectedArticlePseudoPrice, $updatedArticlePseudoPrice[0]['pseudoprice']);
    }

    public function test_import_should_update_price_group()
    {
        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdArticleOrderNumber = 'SW10003';
        $expectedArticlePriceGroup = 'EK';

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticlePseudoPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedArticle[0]['articleID']}'");

        $this->assertEquals($expectedArticlePriceGroup, $updatedArticlePseudoPrice[0]['pricegroup']);
    }
}
