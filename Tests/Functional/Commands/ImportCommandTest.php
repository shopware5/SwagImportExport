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
use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use Symfony\Component\Console\Exception\RuntimeException;

class ImportCommandTest extends TestCase
{
    use FixturesImportTrait;
    use CommandTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public const CLI_IMPORT_COMMAND = 'sw:importexport:import -p';

    private Connection $connection;

    public function setUp(): void
    {
        $this->connection = $this->getContainer()->get('dbal_connection');
    }

    public function testCustomerXmlImport(): void
    {
        $config = $this->getContainer()->get(\Shopware_Components_Config::class);
        $previousBatchSize = (int) $config->getByNamespace('SwagImportExport', 'batch-size-import', 50);
        $this->getContainer()->get('config_writer')->save('batch-size-import', 1);
        $this->getContainer()->get(\Zend_Cache_Core::class)->clean();
        $config->setShop(Shopware()->Shop());

        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $profile = 'default_customers';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'CustomerImport.xml';
        $expectedImportedCustomersAmount = 4;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $importedCustomersAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame(sprintf('Total count: %s.', $expectedImportedCustomersAmount), $consoleOutput[3]);
        static::assertSame($expectedImportedCustomersAmount, $importedCustomersAmount);
        static::assertSame('Processed default_customers: 1.', $consoleOutput[4]);

        $this->getContainer()->get('config_writer')->save('batch-size-import', $previousBatchSize);
        $this->getContainer()->get(\Zend_Cache_Core::class)->clean();
        $config->setShop(Shopware()->Shop());
    }

    public function testCustomerCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $profile = 'default_customers';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'CustomerImport.csv';
        $expectedImportedCustomersAmount = 4;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CUSTOMER_TABLE);
        $importedCustomersAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame(sprintf('Total count: %s.', $expectedImportedCustomersAmount), $consoleOutput[3]);
        static::assertSame($expectedImportedCustomersAmount, $importedCustomersAmount);
    }

    public function testCategoryXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $profile = 'default_categories';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'CategoriesImport.xml';
        $expectedImportedCategories = 16;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $importedCategoriesAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedCategories}.", $consoleOutput[3]);
        static::assertSame($expectedImportedCategories, $importedCategoriesAmount);
    }

    public function testCategoryCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $profile = 'default_categories';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'CategoriesImport.csv';
        $expectedImportedCategories = 16;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::CATEGORY_TABLE);
        $importedCategoriesAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedCategories}.", $consoleOutput[3]);
        static::assertSame($expectedImportedCategories, $importedCategoriesAmount);
    }

    public function testNewsletterRecipientXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $profile = 'default_newsletter_recipient';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'NewsletterRecipientImport.xml';
        $expectedImportedNewsletterRecipients = 6;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $importedNewsletterAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedNewsletterRecipients}.", $consoleOutput[3]);
        static::assertSame($expectedImportedNewsletterRecipients, $importedNewsletterAmount);
    }

    public function testNewsletterRecipientCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $profile = 'default_newsletter_recipient';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'NewsletterRecipientImport.csv';
        $expectedImportedNewsletterRecipients = 6;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::NEWSLETTER_TABLE);
        $importedNewsletterAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedNewsletterRecipients}.", $consoleOutput[3]);
        static::assertSame($expectedImportedNewsletterRecipients, $importedNewsletterAmount);
    }

    public function testProductXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $profile = 'default_articles';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.xml';
        $expectedImportedProducts = 2;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $importedProductsAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedProducts}.", $consoleOutput[3]);
        static::assertSame($expectedImportedProducts, $importedProductsAmount);
    }

    public function testProductCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $profile = 'default_articles';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.csv';
        $expectedImportedProducts = 2;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::PRODUCT_TABLE);
        $importedProductsAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedProducts}.", $consoleOutput[3]);
        static::assertSame($expectedImportedProducts, $importedProductsAmount);
    }

    public function testVariantXmlImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $profile = 'default_article_variants_minimal';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'VariantsImport.xml';
        $expectedImportedVariants = 2;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $importedVariantsAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame("Total count: {$expectedImportedVariants}.", $consoleOutput[3]);
        static::assertSame($expectedImportedVariants, $importedVariantsAmount);
    }

    public function testVariantCsvImport(): void
    {
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $profile = 'default_article_variants_minimal';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'VariantsImport.csv';
        $expectedImportedVariants = 2;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::VARIANT_TABLE);
        $importedVariantsAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame('Using format: csv.', $consoleOutput[1], 'Returned not the expected export file format.');
        static::assertSame("Total count: {$expectedImportedVariants}.", $consoleOutput[3], 'Did not process the expected amount of data rows.');
        static::assertSame($expectedImportedVariants, $importedVariantsAmount, 'Expected 2 new rows in s_articles_details.');
    }

    public function testProductInStockXmlImport(): void
    {
        $profile = 'default_article_in_stock';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleInStockImport.xml';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 405.', $consoleOutput[3]);
    }

    public function testProductInStockCsvImport(): void
    {
        $profile = 'default_article_in_stock';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleInStockImport.csv';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 405.', $consoleOutput[3]);
    }

    public function testProductPriceXmlImport(): void
    {
        $profile = 'default_article_prices';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticlePricesImport.xml';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 27.', $consoleOutput[3]);
    }

    public function testProductPriceCsvImport(): void
    {
        $profile = 'default_article_prices';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticlePricesImport.csv';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 98.', $consoleOutput[3]);
    }

    public function testProductTranslationXmlImport(): void
    {
        $profile = 'default_article_translations';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleTranslationImport.xml';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 103.', $consoleOutput[3]);
    }

    public function testProductTranslationCsvImport(): void
    {
        $profile = 'default_article_translations';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleTranslationImport.csv';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 103.', $consoleOutput[3]);
    }

    public function testOrderXmlImport(): void
    {
        $profile = 'default_orders_minimal';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'OrderImport.xml';
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $expectedImportedOrders = 0;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $importedOrdersAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame($expectedImportedOrders, $importedOrdersAmount);
        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 17.', $consoleOutput[3]);
    }

    public function testOrderCsvImport(): void
    {
        $profile = 'default_orders_minimal';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'OrderImport.csv';
        $actualLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $expectedImportedOrders = 0;

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        $resultLineAmount = $this->getRowCountForTable(ProfileDataProvider::ORDER_TABLE);
        $importedOrdersAmount = $resultLineAmount - $actualLineAmount;

        static::assertSame($expectedImportedOrders, $importedOrdersAmount);
        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 17.', $consoleOutput[3]);
    }

    public function testMainOrderXmlImport(): void
    {
        $profile = 'default_order_main_data';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'MainOrderImport.xml';

        $this->expectException(\Exception::class);
        $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");
    }

    public function testMainOrderCsvImport(): void
    {
        $profile = 'default_order_main_data';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'MainOrderImport.csv';

        $this->expectException(\Exception::class);
        $this->runCommand(self::CLI_IMPORT_COMMAND . " {$profile} {$filePath}");
    }

    public function testTranslationXmlImport(): void
    {
        $profile = 'default_system_translations';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'TranslationImport.xml';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 15.', $consoleOutput[3]);
    }

    public function testTranslationCsvImport(): void
    {
        $profile = 'default_system_translations';
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'TranslationImport.csv';

        $consoleOutput = $this->runCommand(sprintf('%s %s %s', self::CLI_IMPORT_COMMAND, $profile, $filePath));

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 15.', $consoleOutput[3]);
    }

    public function testExportCommandWithUnknownProfile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Profile not found by name "unknown_profile".');
        $this->runCommand('sw:importexport:import -p unknown_profile product.csv');
    }

    public function testExportCommandProfileNameDetectionByFileName(): void
    {
        $filePath = \ImportExportTestKernel::IMPORT_FILES_DIR . 'test.default_customers.xml';
        $consoleOutput = $this->runCommand('sw:importexport:import ' . $filePath);
        static::assertSame('Using profile: default_customers.', $consoleOutput[0]);
    }

    public function testExportCommandWithUnknownProfileThroughFileNameDetection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Profile could not be determinated by file path "product.csv".');
        $this->runCommand('sw:importexport:import product.csv');
    }

    public function testExportCommandWithoutFileName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "filepath").');
        $this->runCommand('sw:importexport:import');
    }

    public function testExportCommandWithInvalidFileExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file format: "txt"! Valid file formats are: CSV and XML.');
        $this->runCommand('sw:importexport:import -p default_articles product.txt');
    }

    public function testExportCommandWithInvalidFilePath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('File "product.csv" not found');
        $this->runCommand('sw:importexport:import -p default_articles product.csv');
    }

    private function getRowCountForTable(string $table): int
    {
        $statement = $this->connection->executeQuery(sprintf('SELECT * FROM %s', $table));

        return (int) $statement->rowCount();
    }
}
