<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Shopware\Commands\SwagImportExport\ExportCommand;
use Tests\Helper\CommandTestHelper;

class ExportCommandTest extends \Enlight_Components_Test_Plugin_TestCase
{
    /**
     * @var CommandTestHelper
     */
    private $commandTestHelper;

    public function setUp()
    {
        $this->commandTestHelper = Shopware()->Container()->get('swag_import_export.tests.command_test_helper');
        $this->commandTestHelper->setUp();
    }

    protected function tearDown()
    {
        $this->commandTestHelper->tearDown();
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesCsvExportCommand()
    {
        $expectedLineAmount = 290;
        $profileName = CommandTestHelper::ARTICLE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'article.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 225.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testVariantsCsvExportCommand()
    {
        $expectedLineAmount = 525;
        $profileName = CommandTestHelper::ARTICLE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'variants.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -x {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 400.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testCustomerCsvExportCommand()
    {
        $expectedLineAmount = 3;
        $profileName = CommandTestHelper::CUSTOMER_PROFILE_NAME;

        $fileName = uniqid('test_') . 'customer.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testCategoriesCsvExportCommand()
    {
        $expectedLineAmount = 65;
        $profileName = CommandTestHelper::CATEGORY_PROFILE_NAME;

        $fileName = uniqid('test_') . 'categories.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 62.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesInStockCsvExportCommand()
    {
        $expectedLineAmount = 406;
        $profileName = CommandTestHelper::ARTICLES_INSTOCK_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesinstock.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 400.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesPricesCsvExportCommand()
    {
        $expectedLineAmount = 406;
        $profileName = CommandTestHelper::ARTICLES_PRICES_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesprices.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 405.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     * @expectedException \InvalidArgumentException
     */
    public function testArticlesImagesCsvExportCommand()
    {
        $profileName = CommandTestHelper::ARTICLES_IMAGE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesimage.csv';

        $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 111;
        $profileName = CommandTestHelper::ARTICLES_TRANSLATIONS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlestranslation.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testOrderCsvExportCommand()
    {
        $expectedLineAmount = 18;
        $profileName = CommandTestHelper::ORDERS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'order.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 17.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testMainOrderCsvExportCommand()
    {
        $expectedLineAmount = 5;
        $profileName = CommandTestHelper::MAIN_ORDERS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'mainorder.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 4.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 16;
        $profileName = CommandTestHelper::TRANSLATIONS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'translation.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testNewsletterCsvExportCommand()
    {
        $this->commandTestHelper->createNewsletterDemoData();

        $expectedLineAmount = 26;
        $profileName = CommandTestHelper::NEWSLETTER_PROFILE_NAME;

        $fileName = uniqid('test_') . 'newsletter.csv';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 25.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 6362;
        $profileName = CommandTestHelper::ARTICLE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'article.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 100 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 100.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testVariantsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 6362;
        $profileName = CommandTestHelper::ARTICLE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'variants.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 100 -x {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 100.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testCustomerXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 42;
        $profileName = CommandTestHelper::CUSTOMER_PROFILE_NAME;

        $fileName = uniqid('test_') . 'customer.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 1 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 1.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testCategoriesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1010;
        $profileName = CommandTestHelper::CATEGORY_PROFILE_NAME;

        $fileName = uniqid('test_') . 'categories.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 40 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 40.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesInStockXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1408;
        $profileName = CommandTestHelper::ARTICLES_INSTOCK_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesinstock.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 200 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 200.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesPricesXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 1108;
        $profileName = CommandTestHelper::ARTICLES_PRICES_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesprices.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 100 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 100.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     * @expectedException \InvalidArgumentException
     */
    public function testArticlesImagesXmlExportCommandWithLimit()
    {
        $profileName = CommandTestHelper::ARTICLES_IMAGE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesimage.xml';

        $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 50 {$fileName}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesTranslationsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 942;
        $profileName = CommandTestHelper::ARTICLES_TRANSLATIONS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlestranslation.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testMainOrderXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 109;
        $profileName = CommandTestHelper::MAIN_ORDERS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'mainorder.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 2 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testTranslationsXmlExportCommandWithLimit()
    {
        $expectedLineAmount = 88;
        $profileName = CommandTestHelper::TRANSLATIONS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'translation.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 10 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 10.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testNewsletterXmlExportCommandWithLimit()
    {
        $this->commandTestHelper->createNewsletterDemoData();

        $expectedLineAmount = 200;
        $profileName = CommandTestHelper::NEWSLETTER_PROFILE_NAME;

        $fileName = uniqid('test_') . 'newsletter.xml';
        $this->commandTestHelper->addFile($fileName);

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -l 15 {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");
    }

    /**
     * @param string $consoleOutput
     * @return array
     */
    private function convertOutputToArrayByLineBreak($consoleOutput)
    {
        return explode(PHP_EOL, $consoleOutput);
    }
}
