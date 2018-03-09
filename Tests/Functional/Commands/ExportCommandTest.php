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
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class ExportCommandTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use FixturesImportTrait;

    public function testArticlesCsvExportCommand()
    {
        $expectedLineAmount = 290;
        $profileName = ProfileDataProvider::ARTICLE_PROFILE_NAME;

        $fileName = 'article.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 225.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testVariantsCsvExportCommand()
    {
        $expectedLineAmount = 525;
        $profileName = ProfileDataProvider::ARTICLE_PROFILE_NAME;

        $fileName = 'variants.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -x {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 400.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCustomerCsvExportCommand()
    {
        $expectedLineAmount = 3;
        $profileName = ProfileDataProvider::CUSTOMER_PROFILE_NAME;

        $fileName = 'customer.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCategoriesCsvExportCommand()
    {
        $expectedLineAmount = 65;
        $profileName = ProfileDataProvider::CATEGORY_PROFILE_NAME;

        $fileName = 'categories.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 62.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesInStockCsvExportCommand()
    {
        $expectedLineAmount = 405;
        $profileName = ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_NAME;

        $fileName = 'articlesinstock.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 400.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesPricesCsvExportCommand()
    {
        $expectedLineAmount = 406;
        $profileName = ProfileDataProvider::ARTICLES_PRICES_PROFILE_NAME;

        $fileName = 'articlesprices.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 405.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesImagesCsvExportCommand()
    {
        $profileName = ProfileDataProvider::ARTICLES_IMAGE_PROFILE_NAME;

        $fileName = 'articlesimage.csv';

        $this->expectException(\InvalidArgumentException::class);
        $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
    }

    public function testArticlesTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 111;
        $profileName = ProfileDataProvider::ARTICLES_TRANSLATIONS_PROFILE_NAME;

        $fileName = 'articlestranslation.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testOrderCsvExportCommand()
    {
        $expectedLineAmount = 18;
        $profileName = ProfileDataProvider::ORDERS_PROFILE_NAME;

        $fileName = 'order.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 17.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testMainOrderCsvExportCommand()
    {
        $expectedLineAmount = 5;
        $profileName = ProfileDataProvider::MAIN_ORDERS_PROFILE_NAME;

        $fileName = 'mainorder.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 4.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 16;
        $profileName = ProfileDataProvider::TRANSLATIONS_PROFILE_NAME;

        $fileName = 'translation.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testNewsletterCsvExportCommand()
    {
        $this->importNewsletterDemoData();

        $expectedLineAmount = 26;
        $profileName = ProfileDataProvider::NEWSLETTER_PROFILE_NAME;

        $fileName = 'newsletter.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 25.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 6362;
        $profileName = ProfileDataProvider::ARTICLE_PROFILE_NAME;

        $fileName = 'article.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 100 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 100.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testVariantsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 6362;
        $profileName = ProfileDataProvider::ARTICLE_PROFILE_NAME;

        $fileName = 'variants.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 100 -x {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 100.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCustomerXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 42;
        $profileName = ProfileDataProvider::CUSTOMER_PROFILE_NAME;

        $fileName = 'customer.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 1 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 1.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testCategoriesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1010;
        $profileName = ProfileDataProvider::CATEGORY_PROFILE_NAME;

        $fileName = 'categories.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 40 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 40.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesInStockXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1408;
        $profileName = ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_NAME;

        $fileName = 'articlesinstock.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 200 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 200.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesPricesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1108;
        $profileName = ProfileDataProvider::ARTICLES_PRICES_PROFILE_NAME;

        $fileName = 'articlesprices.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 100 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 100.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testArticlesImagesXmlExportCommandWithLimit()
    {
        $profileName = ProfileDataProvider::ARTICLES_IMAGE_PROFILE_NAME;

        $fileName = 'articlesimage.xml';

        $this->expectException(\InvalidArgumentException::class);
        $this->runCommand("sw:importexport:export -p {$profileName} -l 50 {$fileName}");
    }

    public function testArticlesTranslationsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 942;
        $profileName = ProfileDataProvider::ARTICLES_TRANSLATIONS_PROFILE_NAME;

        $fileName = 'articlestranslation.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testMainOrderXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 109;
        $profileName = ProfileDataProvider::MAIN_ORDERS_PROFILE_NAME;

        $fileName = 'mainorder.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 2 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testTranslationsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 88;
        $profileName = ProfileDataProvider::TRANSLATIONS_PROFILE_NAME;

        $fileName = 'translation.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 10 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 10.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    public function testNewsletterXmlExportCommandWithLimit()
    {
        $this->importNewsletterDemoData();

        $expectedLineAmount = 200;
        $profileName = ProfileDataProvider::NEWSLETTER_PROFILE_NAME;

        $fileName = 'newsletter.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand("sw:importexport:export -p {$profileName} -l 15 {$fileName}");

        $fp = file($this->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }
}
