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

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesCsvExportCommand()
    {
        $expectedLineAmount = 290;
        $profileName = CommandTestHelper::ARTICLE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'article.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 225.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testVariantsCsvExportCommand()
    {
        $expectedLineAmount = 525;
        $profileName = CommandTestHelper::ARTICLE_PROFILE_NAME;

        $fileName = uniqid('test_') . 'variants.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} -x {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 400.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testCustomerXmlExportCommand()
    {
        $expectedLineAmount = 76;
        $profileName = CommandTestHelper::CUSTOMER_PROFILE_NAME;

        $fileName = uniqid('test_') . 'customer.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testCategoriesCsvExportCommand()
    {
        $expectedLineAmount = 65;
        $profileName = CommandTestHelper::CATEGORY_PROFILE_NAME;

        $fileName = uniqid('test_') . 'categories.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 62.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesInStockCsvExportCommand()
    {
        $expectedLineAmount = 406;
        $profileName = CommandTestHelper::ARTICLES_INSTOCK_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesinstock.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 400.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testArticlesPricesCsvExportCommand()
    {
        $expectedLineAmount = 406;
        $profileName = CommandTestHelper::ARTICLES_PRICES_PROFILE_NAME;

        $fileName = uniqid('test_') . 'articlesprices.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 405.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     * @expectedException \InvalidArgumentException
     */
    public function testArticlesImagesCsvExportCommand()
    {
        $expectedLineAmount = 406;
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

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testOrderCsvExportCommand()
    {
        $expectedLineAmount = 18;
        $profileName = CommandTestHelper::ORDERS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'order.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 17.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testMainOrderCsvExportCommand()
    {
        $expectedLineAmount = 5;
        $profileName = CommandTestHelper::MAIN_ORDERS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'mainorder.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 4.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    /**
     * @covers ExportCommand::execute()
     */
    public function testTranslationsCsvExportCommand()
    {
        $expectedLineAmount = 16;
        $profileName = CommandTestHelper::TRANSLATIONS_PROFILE_NAME;

        $fileName = uniqid('test_') . 'translation.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
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

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:export -p {$profileName} {$fileName}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $fp = file($this->commandTestHelper->getFilePath($fileName));
        $lineAmount = count($fp);

        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 25.', $consoleOutput[3]);
        $this->assertEquals($expectedLineAmount, $lineAmount, "Expected {$expectedLineAmount} lines, found {$lineAmount}");

        $this->commandTestHelper->deleteFile($fileName);
    }

    protected function tearDown()
    {
        $this->commandTestHelper->tearDown();
    }

    /**
     * @param string $consoleOutput
     * @return array
     */
    private function convertOutputToArrayByLineBreak($consoleOutput)
    {
        return preg_split('/\R/', $consoleOutput);
    }
}
