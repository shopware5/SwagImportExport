<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Behat\Testwork\Cli\Command;
use Doctrine\DBAL\Connection;
use Tests\Helper\CommandTestHelper;

class ImportCommandTest extends \Enlight_Components_Test_Plugin_TestCase
{
    /**
     * @var CommandTestHelper
     */
    private $commandTestHelper;

    /**
     * @var Connection
     */
    private $connection;

    public function setUp()
    {
        $this->commandTestHelper = Shopware()->Container()->get('swag_import_export.tests.command_test_helper');
        $this->commandTestHelper->setUp();
        $this->connection = Shopware()->Container()->get('dbal_connection');
    }

    protected function tearDown()
    {
        $this->commandTestHelper->tearDown();
    }

    public function testCustomerXmlImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::CUSTOMER_TABLE);
        $customerProfileName = CommandTestHelper::CUSTOMER_PROFILE_NAME;
        $importFilePath = CommandTestHelper::IMPORT_FILES_DIR . 'CustomerImport.xml';
        $expectedImportedCustomersAmount = 4;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$customerProfileName} {$importFilePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::CUSTOMER_TABLE);
        $importedCustomersAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: customer_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedCustomersAmount}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedCustomersAmount, $importedCustomersAmount);
    }

    public function testCustomerCsvImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::CUSTOMER_TABLE);
        $customerProfileName = CommandTestHelper::CUSTOMER_PROFILE_NAME;
        $importFilePath = CommandTestHelper::IMPORT_FILES_DIR . 'CustomerImport.csv';
        $expectedImportedCustomersAmount = 4;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$customerProfileName} {$importFilePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::CUSTOMER_TABLE);
        $importedCustomersAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: customer_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedCustomersAmount}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedCustomersAmount, $importedCustomersAmount);
    }

    public function testCategoryXmlImport()
    {
        $this->markTestSkipped('Fix category import over cli commands');

        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::CATEGORY_TABLE);
        $categoryProfileName = CommandTestHelper::CATEGORY_PROFILE_NAME;
        $importFilePath = CommandTestHelper::IMPORT_FILES_DIR . 'CategoriesImport.xml';
        $expectedImportedCategories = 16;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$categoryProfileName} {$importFilePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::CATEGORY_TABLE);
        $importedCategoriesAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: category_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedCategories}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedCategories, $importedCategoriesAmount);
    }

    public function testCategoryCsvImport()
    {
        $this->markTestSkipped('Fix category import over cli commands');

        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::CATEGORY_TABLE);
        $profile = CommandTestHelper::CATEGORY_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'CategoriesImport.csv';
        $expectedImportedCategories = 16;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::CATEGORY_TABLE);
        $importedCategoriesAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: category_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedCategories}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedCategories, $importedCategoriesAmount);
    }

    public function testNewsletterRecipientXmlImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::NEWSLETTER_TABLE);
        $profile = CommandTestHelper::NEWSLETTER_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'NewsletterRecipientImport.xml';
        $expectedImportedNewsletterRecipients = 6;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::NEWSLETTER_TABLE);
        $importedNewsletterAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: newsletter_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedNewsletterRecipients}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedNewsletterRecipients, $importedNewsletterAmount);
    }

    public function testNewsletterRecipientCsvImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::NEWSLETTER_TABLE);
        $profile = CommandTestHelper::NEWSLETTER_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'NewsletterRecipientImport.csv';
        $expectedImportedNewsletterRecipients = 6;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::NEWSLETTER_TABLE);
        $importedNewsletterAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: newsletter_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedNewsletterRecipients}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedNewsletterRecipients, $importedNewsletterAmount);
    }

    public function testArticleXmlImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::ARTICLE_TABLE);
        $profile = CommandTestHelper::ARTICLE_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticleImport.xml';
        $expectedImportedArticles = 2;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::ARTICLE_TABLE);
        $importedArticlesAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: article_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedArticles}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedArticles, $importedArticlesAmount);
    }

    public function testArticleCsvImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::ARTICLE_TABLE);
        $profile = CommandTestHelper::ARTICLE_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticleImport.csv';
        $expectedImportedArticles = 2;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::ARTICLE_TABLE);
        $importedArticlesAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: article_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedArticles}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedArticles, $importedArticlesAmount);
    }

    public function testVariantXmlImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::VARIANT_TABLE);
        $profile = CommandTestHelper::VARIANT_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'VariantsImport.xml';
        $expectedImportedVariants = 5;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::VARIANT_TABLE);
        $importedVariantsAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: variant_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedVariants}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedVariants, $importedVariantsAmount);
    }

    public function testVariantCsvImport()
    {
        $actualLineAmount = $this->getRowCountForTable(CommandTestHelper::VARIANT_TABLE);
        $profile = CommandTestHelper::VARIANT_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'VariantsImport.csv';
        $expectedImportedVariants = 5;

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $resultLineAmount = $this->getRowCountForTable(CommandTestHelper::VARIANT_TABLE);
        $importedVariantsAmount = $resultLineAmount - $actualLineAmount;

        $this->assertEquals('Using profile: variant_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals("Total count: {$expectedImportedVariants}.", $consoleOutput[3]);
        $this->assertEquals($expectedImportedVariants, $importedVariantsAmount);
    }

    public function testArticleInStockXmlImport()
    {
        $profile = CommandTestHelper::ARTICLES_INSTOCK_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticleInStockImport.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: article_instock_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 405.', $consoleOutput[3]);
    }

    public function testArticleInStockCsvImport()
    {
        $profile = CommandTestHelper::ARTICLES_INSTOCK_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticleInStockImport.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: article_instock_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 405.', $consoleOutput[3]);
    }

    public function testArticlePriceXmlImport()
    {
        $profile = CommandTestHelper::ARTICLES_PRICES_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticlePricesImport.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: articles_price_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 27.', $consoleOutput[3]);
    }

    public function testArticlePriceCsvImport()
    {
        $profile = CommandTestHelper::ARTICLES_PRICES_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticlePricesImport.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: articles_price_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 98.', $consoleOutput[3]);
    }

    public function testArticleTranslationXmlImport()
    {
        $profile = CommandTestHelper::ARTICLES_TRANSLATIONS_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticleTranslationImport.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: articles_translations_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
    }

    public function testArticleTranslationCsvImport()
    {
        $profile = CommandTestHelper::ARTICLES_TRANSLATIONS_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'ArticleTranslationImport.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: articles_translations_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 103.', $consoleOutput[3]);
    }

    public function testOrderXmlImport()
    {
        $this->markTestSkipped('Fix order import.');

        $profile = CommandTestHelper::ORDERS_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'OrderImport.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: order_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
    }

    public function testOrderCsvImport()
    {
        $this->markTestSkipped('Fix order import.');

        $profile = CommandTestHelper::ORDERS_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'OrderImport.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: order_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
    }

    public function testMainOrderXmlImport()
    {
        $this->markTestSkipped('Fix main order import.');

        $profile = CommandTestHelper::IMPORT_MAIN_ORDER_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'MainOrderImport.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: main_order_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
    }

    public function testMainOrderCsvImport()
    {
        $this->markTestSkipped('Fix main order import.');
        
        $profile = CommandTestHelper::IMPORT_MAIN_ORDER_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'MainOrderImport.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: main_order_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 2.', $consoleOutput[3]);
    }

    public function testTranslationXmlImport()
    {
        $profile = CommandTestHelper::TRANSLATIONS_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'TranslationImport.xml';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: translation_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: xml.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
    }

    public function testTranslationCsvImport()
    {
        $profile = CommandTestHelper::TRANSLATIONS_PROFILE_NAME;
        $filePath = CommandTestHelper::IMPORT_FILES_DIR . 'TranslationImport.csv';

        $consoleOutput = $this->commandTestHelper->runCommand("sw:importexport:import -p {$profile} {$filePath}");
        $consoleOutput = $this->convertOutputToArrayByLineBreak($consoleOutput);

        $this->assertEquals('Using profile: translation_profile.', $consoleOutput[0]);
        $this->assertEquals('Using format: csv.', $consoleOutput[1]);
        $this->assertEquals('Total count: 15.', $consoleOutput[3]);
    }

    /**
     * @param string $consoleOutput
     * @return array
     */
    private function convertOutputToArrayByLineBreak($consoleOutput)
    {
        return explode(PHP_EOL, $consoleOutput);
    }

    /**
     * @param string $table
     * @return int
     */
    private function getRowCountForTable($table)
    {
        $statement = $this->connection->executeQuery("SELECT * FROM {$table}");
        return $statement->rowCount();
    }
}
