<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Commands;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class ExportCommandTest extends TestCase
{
    use FixturesImportTrait;
    use CommandTestCaseTrait;
    use DatabaseTestCaseTrait;

    public function testArticlesCsvExportCommand()
    {
        $expectedLineAmount = 290;
        $profileName = 'default_articles';

        $fileName = 'article.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 225.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testVariantsCsvExportCommand()
    {
        $expectedLineAmount = 525;
        $profileName = 'default_articles';

        $fileName = 'variants.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -x {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 400.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCustomerCsvExportCommand()
    {
        $expectedLineAmount = 3;
        $profileName = 'default_customers_complete';

        $fileName = 'customer.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 2.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCategoriesCsvExportCommand()
    {
        $expectedLineAmount = 65;
        $profileName = 'default_categories';

        $fileName = 'categories.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 62.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesInStockCsvExportCommand()
    {
        $expectedLineAmount = 405;
        $profileName = 'default_article_in_stock';

        $fileName = 'articlesinstock.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 400.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesPricesCsvExportCommand()
    {
        $expectedLineAmount = 406;
        $profileName = 'default_article_prices';

        $fileName = 'articlesprices.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 405.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesImagesCsvExportCommand()
    {
        $profileName = 'default_article_images';

        $fileName = 'articlesimage.csv';

        $this->expectException(\InvalidArgumentException::class);
        $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
    }

    public function testArticlesTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 233;
        $profileName = 'default_article_translations_update';

        $fileName = 'articlestranslation.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 225.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testOrderCsvExportCommand()
    {
        $expectedLineAmount = 18;
        $profileName = 'default_orders_minimal';

        $fileName = 'order.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 17.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testMainOrderCsvExportCommand()
    {
        $expectedLineAmount = 5;
        $profileName = 'default_order_main_data';

        $fileName = 'mainorder.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 4.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 16;
        $profileName = 'default_system_translations';

        $fileName = 'translation.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 15.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testNewsletterCsvExportCommand()
    {
        $this->importNewsletterDemoData();

        $expectedLineAmount = 26;
        $profileName = 'default_newsletter_recipient';

        $fileName = 'newsletter.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 25.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 6462;
        $profileName = 'default_articles';

        $fileName = 'article.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 100 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 100.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testVariantsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 6462;
        $profileName = 'default_articles';

        $fileName = 'variants.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 100 -x {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 100.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCustomerXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 43;
        $profileName = 'default_customers';

        $fileName = 'customer.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 1 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 1.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCategoriesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 208;
        $profileName = 'default_categories_minimal';

        $fileName = 'categories.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 40 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 40.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesInStockXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1408;
        $profileName = 'default_article_in_stock';

        $fileName = 'articlesinstock.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 200 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 200.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesPricesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1208;
        $profileName = 'default_article_prices';

        $fileName = 'articlesprices.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 100 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 100.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesImagesXmlExportCommandWithLimit()
    {
        $profileName = 'default_article_images';

        $fileName = 'articlesimage.xml';

        $this->expectException(\InvalidArgumentException::class);
        $this->runCommand("sw:importexport:export -p {$profileName} -l 50 {$fileName}");
    }

    public function testArticlesTranslationsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 2262;
        $profileName = 'default_article_translations_update';

        $fileName = 'articlestranslation.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 225.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testMainOrderXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 111;
        $profileName = 'default_order_main_data';

        $fileName = 'mainorder.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 2 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 2.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testTranslationsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 88;
        $profileName = 'default_system_translations';

        $fileName = 'translation.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 10 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 10.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testNewsletterXmlExportCommandWithLimit()
    {
        $this->importNewsletterDemoData();

        $expectedLineAmount = 230;
        $profileName = 'default_newsletter_recipient';

        $fileName = 'newsletter.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 15 {$fileName}");

        $fp = \file($this->getFilePath($fileName));
        $lineAmount = \count($fp);

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 15.', $consoleOutput[3]);
        static::assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }
}
