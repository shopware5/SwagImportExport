<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Commands;

use Doctrine\DBAL\Connection;
use ImportExportTestKernel;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class ImportCommandTest extends TestCase
{
    use FixturesImportTrait;
    use CommandTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public const CLI_IMPORT_COMMAND = 'sw:importexport:import -p';

    private Connection $connection;

    public function setUp(): void
    {
        $this->connection = $this->getContainer()->get('dbal_connection');
    }

    public function testCustomerXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $customerProfileName = 'default_customers';
        $importFilePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'CustomerImport.xml';
        $expectedImportedCustomersAmount = 4;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$customerProfileName} {$importFilePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $importedCustomersAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedCustomersAmount}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedCustomersAmount, $importedCustomersAmount);
    }

    public function testCustomerCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $customerProfileName = 'default_customers';
        $importFilePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'CustomerImport.csv';
        $expectedImportedCustomersAmount = 4;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$customerProfileName} {$importFilePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $importedCustomersAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedCustomersAmount}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedCustomersAmount, $importedCustomersAmount);
    }

    public function testCategoryXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $categoryProfileName = 'default_categories';
        $importFilePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'CategoriesImport.xml';
        $expectedImportedCategories = 16;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$categoryProfileName} {$importFilePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $importedCategoriesAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedCategories}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedCategories, $importedCategoriesAmount);
    }

    public function testCategoryCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $profile = 'default_categories';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'CategoriesImport.csv';
        $expectedImportedCategories = 16;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $importedCategoriesAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedCategories}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedCategories, $importedCategoriesAmount);
    }

    public function testNewsletterRecipientXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $profile = 'default_newsletter_recipient';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'NewsletterRecipientImport.xml';
        $expectedImportedNewsletterRecipients = 6;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $importedNewsletterAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedNewsletterRecipients}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedNewsletterRecipients, $importedNewsletterAmount);
    }

    public function testNewsletterRecipientCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $profile = 'default_newsletter_recipient';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'NewsletterRecipientImport.csv';
        $expectedImportedNewsletterRecipients = 6;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $importedNewsletterAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedNewsletterRecipients}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedNewsletterRecipients, $importedNewsletterAmount);
    }

    public function testProductXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $profile = 'default_articles';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.xml';
        $expectedImportedProducts = 2;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $importedProductsAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedProducts}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedProducts, $importedProductsAmount);
    }

    public function testProductCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $profile = 'default_articles';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.csv';
        $expectedImportedProducts = 2;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $importedProductsAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedProducts}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedProducts, $importedProductsAmount);
    }

    public function testVariantXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $profile = 'default_article_variants_minimal';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'VariantsImport.xml';
        $expectedImportedVariants = 2;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $importedVariantsAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals("Total count: {$expectedImportedVariants}.", $consoleOutput[3]);
        static::assertEquals($expectedImportedVariants, $importedVariantsAmount);
    }

    public function testVariantCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $profile = 'default_article_variants_minimal';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'VariantsImport.csv';
        $expectedImportedVariants = 2;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $importedVariantsAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals('Using format: csv.', $consoleOutput[1], 'Returned not the expected export file format.');
        static::assertEquals("Total count: {$expectedImportedVariants}.", $consoleOutput[3], 'Did not process the expected amount of data rows.');
        static::assertEquals($expectedImportedVariants, $importedVariantsAmount, 'Expected 2 new rows in s_articles_details.');
    }

    public function testProductInStockXmlImport(): void
    {
        $profile = 'default_article_in_stock';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleInStockImport.xml';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 405.', $consoleOutput[3]);
    }

    public function testProductInStockCsvImport(): void
    {
        $profile = 'default_article_in_stock';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleInStockImport.csv';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 405.', $consoleOutput[3]);
    }

    public function testProductPriceXmlImport(): void
    {
        $profile = 'default_article_prices';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticlePricesImport.xml';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 27.', $consoleOutput[3]);
    }

    public function testProductPriceCsvImport(): void
    {
        $profile = 'default_article_prices';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticlePricesImport.csv';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 98.', $consoleOutput[3]);
    }

    public function testProductTranslationXmlImport(): void
    {
        $profile = 'default_article_translations';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleTranslationImport.xml';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 103.', $consoleOutput[3]);
    }

    public function testProductTranslationCsvImport(): void
    {
        $profile = 'default_article_translations';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleTranslationImport.csv';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 103.', $consoleOutput[3]);
    }

    public function testOrderXmlImport(): void
    {
        $profile = 'default_orders_minimal';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'OrderImport.xml';
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $expectedImportedOrders = 0;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $importedOrdersAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals($expectedImportedOrders, $importedOrdersAmount);
        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 17.', $consoleOutput[3]);
    }

    public function testOrderCsvImport(): void
    {
        $profile = 'default_orders_minimal';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'OrderImport.csv';
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $expectedImportedOrders = 0;

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $importedOrdersAmount = $resultLineAmount - $actualLineAmount;

        static::assertEquals($expectedImportedOrders, $importedOrdersAmount);
        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 17.', $consoleOutput[3]);
    }

    public function testMainOrderXmlImport(): void
    {
        $profile = 'default_order_main_data';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'MainOrderImport.xml';

        $this->expectException(\Exception::class);
        $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");
    }

    public function testMainOrderCsvImport(): void
    {
        $profile = 'default_order_main_data';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'MainOrderImport.csv';

        $this->expectException(\Exception::class);
        $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");
    }

    public function testTranslationXmlImport(): void
    {
        $profile = 'default_system_translations';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'TranslationImport.xml';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: xml.', $consoleOutput[1]);
        static::assertEquals('Total count: 15.', $consoleOutput[3]);
    }

    public function testTranslationCsvImport(): void
    {
        $profile = 'default_system_translations';
        $filePath = ImportExportTestKernel::IMPORT_FILES_DIR . 'TranslationImport.csv';

        $consoleOutput = $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");

        static::assertEquals('Using format: csv.', $consoleOutput[1]);
        static::assertEquals('Total count: 15.', $consoleOutput[3]);
    }

    private function getRowCountForTable(string $table): int
    {
        $statement = $this->connection->executeQuery("SELECT * FROM {$table}");

        return (int) $statement->rowCount();
    }
}
