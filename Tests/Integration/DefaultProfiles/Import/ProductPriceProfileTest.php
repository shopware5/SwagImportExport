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
use SwagImportExport\Components\Utils\SwagVersionHelper;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ProductPriceProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldUpdateProductPrice(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdProductOrderNumber = 'SW10003';
        $expectedPurchasePrice = 7.95;
        $expectedProductPrice = 8.3613445378151;

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdProductOrderNumber}'");
        $updatedProductPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedProduct[0]['articleID']}'");

        static::assertEquals($expectedPurchasePrice, $updatedProduct[0]['purchaseprice']);
        static::assertEquals($expectedProductPrice, $updatedProductPrice[0]['price']);
    }

    public function testImportShouldUpdateProductPseudoPrice(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdProductOrderNumber = 'SW10003';
        $expectedProductPseudoPrice = 13.403361344538;

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdProductOrderNumber}'");
        $updatedProductPseudoPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedProduct[0]['articleID']}'");

        static::assertEquals($expectedProductPseudoPrice, $updatedProductPseudoPrice[0]['pseudoprice']);
    }

    public function testImportShouldUpdateProductRegulationPrice(): void
    {
        if (!SwagVersionHelper::isShopware578()) {
            static::markTestSkipped('This test is not supported lower shopware version 5.7.8');
        }

        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdProductOrderNumber = 'SW10003';
        $expectedProductRegulationPrice = 13.403361344538;

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdProductOrderNumber}'");
        $updatedProductPseudoPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedProduct[0]['articleID']}'");

        static::assertEquals($expectedProductRegulationPrice, $updatedProductPseudoPrice[0]['regulation_price']);
    }

    public function testImportShouldUpdatePriceGroup(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_price_profile.csv';
        $createdProductOrderNumber = 'SW10003';
        $expectedProductPriceGroup = 'EK';

        $this->runCommand("sw:import:import -p default_article_prices {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdProductOrderNumber}'");
        $updatedProductPseudoPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$updatedProduct[0]['articleID']}'");

        static::assertEquals($expectedProductPriceGroup, $updatedProductPseudoPrice[0]['pricegroup']);
    }
}
