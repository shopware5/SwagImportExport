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
use SwagImportExport\Components\Utils\SwagVersionHelper;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use Symfony\Component\Console\Exception\RuntimeException;

class ExportCommandTest extends TestCase
{
    use FixturesImportTrait;
    use CommandTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testExportCommandOptions(): void
    {
        $consoleOutput = $this->runCommand('sw:importexport:export -p default_articles product.csv -c 14 --productStream 1 --dateFrom 2022-08-01 --dateTo 2022-09-01');

        static::assertSame('Using category as filter: 14.', $consoleOutput[3]);
        static::assertSame('from: 2022-08-01 00:00:00.', $consoleOutput[4]);
        static::assertSame('to: 2022-09-01 23:59:59.', $consoleOutput[5]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid stream: "1"! There is no customer stream with this id.');
        $this->runCommand('sw:importexport:export -p default_customers customer.xml --customerstream 1');
    }

    public function testExportCommandWithProductStreamName(): void
    {
        $productStreamOneId = 1234;
        $productStreamTwoId = 5678;
        $sql = file_get_contents(__DIR__ . '/Fixture/productStreams.sql');
        static::assertIsString($sql);
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeStatement($sql, ['productStreamOneId' => $productStreamOneId, 'productStreamTwoId' => $productStreamTwoId]);

        $consoleOutput = $this->runCommand('sw:importexport:export -p default_articles product.csv --productStream "Foo Bar"');
        static::assertSame(sprintf('Using Product Stream as filter: %d.', $productStreamTwoId), $consoleOutput[3]);
    }

    public function testExportCommandWithProductStreamNameTooManyStreamResults(): void
    {
        $productStreamOneId = 1234;
        $productStreamTwoId = 5678;
        $sql = file_get_contents(__DIR__ . '/Fixture/productStreams.sql');
        static::assertIsString($sql);
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeStatement($sql, ['productStreamOneId' => $productStreamOneId, 'productStreamTwoId' => $productStreamTwoId]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            <<<EOD
There are 2 streams with the name "Foo"

- Test Foo (ID: %d)
- Test Foo Bar (ID: %d)

Please specify more or use the ID.
EOD
            ,
            $productStreamOneId,
            $productStreamTwoId
        ));
        $this->runCommand('sw:importexport:export -p default_articles product.csv --productStream "Foo"');
    }

    public function testExportCommandWithProductStreamNameNoStreamResult(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There are no streams with the name: Foo Bar');
        $this->runCommand('sw:importexport:export -p default_articles product.csv --productStream "Foo Bar"');
    }

    public function testExportCommandCategoryOptionIsNotNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "category" must be a valid ID');
        $this->runCommand('sw:importexport:export -p default_articles product.csv -c Foo');
    }

    public function testExportCommandWithInvalidFromDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid format for "from" date!/');
        $this->runCommand('sw:importexport:export -p default_articles product.csv --dateFrom abc123');
    }

    public function testExportCommandWithInvalidToDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid format for "to" date!/');
        $this->runCommand('sw:importexport:export -p default_articles product.csv --dateTo abc123');
    }

    public function testExportCommandWithFromDateGreaterThanToDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"From" date must be smaller than "to" date');
        $this->runCommand('sw:importexport:export -p default_articles product.csv --dateTo 2022-08-01 --dateFrom 2022-09-01');
    }

    public function testExportCommandWithUnknownProfile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Profile not found by name "unknown_profile".');
        $this->runCommand('sw:importexport:export -p unknown_profile product.csv');
    }

    public function testExportCommandProfileNameDetectionByFileName(): void
    {
        $consoleOutput = $this->runCommand('sw:importexport:export test.default_articles.csv');
        static::assertSame('Using profile: default_articles.', $consoleOutput[0]);
    }

    public function testExportCommandWithUnknownProfileThroughFileNameDetection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Profile could not be determinated by file path "product.csv".');
        $this->runCommand('sw:importexport:export product.csv');
    }

    public function testExportCommandWithoutFileName(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "filepath").');
        $this->runCommand('sw:importexport:export');
    }

    public function testExportCommandCustomerStreamOptionIsNotNumeric(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "customerstream" must be a valid ID');
        $this->runCommand('sw:importexport:export -p default_articles product.csv --customerstream Foo');
    }

    public function testExportCommandWithInvalidFileExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid file format: "txt"! Valid file formats are: CSV and XML.');
        $this->runCommand('sw:importexport:export -p default_articles product.txt');
    }

    public function testExportCommandWithVariantsAndInvalidProfile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can only export variants when exporting the articles profile type.');
        $this->runCommand('sw:importexport:export -p default_addresses addresses.csv --exportVariants');
    }

    public function testExportCommandWithCustomerStream(): void
    {
        $customerStreamId = 1234;
        $sql = file_get_contents(__DIR__ . '/Fixture/customerStreams.sql');
        static::assertIsString($sql);
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeStatement($sql, ['customerStreamId' => $customerStreamId]);

        $consoleOutput = $this->runCommand(
            sprintf('sw:importexport:export -p default_customers customers.csv --customerstream %d', $customerStreamId)
        );
        static::assertStringContainsString(sprintf('Using Customer Stream as filter: %d', $customerStreamId), $consoleOutput[3]);
    }

    public function testExportCommandWithCustomerStreamAndInvalidProfile(): void
    {
        $customerStreamId = 1234;
        $sql = file_get_contents(__DIR__ . '/Fixture/customerStreams.sql');
        static::assertIsString($sql);
        $connection = $this->getContainer()->get(Connection::class);
        $connection->executeStatement($sql, ['customerStreamId' => $customerStreamId]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Customer stream export can not be used with profile: "default_articles"!');
        $this->runCommand(sprintf('sw:importexport:export -p default_articles customers.csv --customerstream %d', $customerStreamId));
    }

    public function testProductsCsvExportCommand(): void
    {
        $expectedLineAmount = 290;
        $profileName = 'default_articles';

        $fileName = 'product.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 225.', $consoleOutput[3]);
        static::assertSame($expectedLineAmount, $lineAmount, sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount));
    }

    public function testVariantsCsvExportCommand(): void
    {
        $expectedLineAmount = 525;
        $profileName = 'default_articles';

        $fileName = 'variants.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -x %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 400.', $consoleOutput[3]);
        static::assertSame($expectedLineAmount, $lineAmount, sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount));
    }

    public function testCustomerCsvExportCommand(): void
    {
        $expectedLineAmount = 3;
        $profileName = 'default_customers_complete';

        $fileName = 'customer.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 2.', $consoleOutput[3]);
        static::assertSame($expectedLineAmount, $lineAmount, sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount));
    }

    public function testCategoriesCsvExportCommand(): void
    {
        $expectedLineAmount = 65;
        $profileName = 'default_categories';

        $fileName = 'categories.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 62.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsInStockCsvExportCommand(): void
    {
        $expectedLineAmount = 405;
        $profileName = 'default_article_in_stock';

        $fileName = 'articlesinstock.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 400.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsPricesCsvExportCommand(): void
    {
        $this->getContainer()->get('config_writer')->save('useCommaDecimal', true, 'SwagImportExport');

        $expectedLineAmount = 406;
        $profileName = 'default_article_prices';

        $fileName = 'articlesprices.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 405.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsImagesCsvExportCommand(): void
    {
        $profileName = 'default_article_images';

        $fileName = 'articlesimage.csv';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('articlesImages profile type is not supported at the moment.');
        $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));
    }

    public function testProductsTranslationsCsvExportCommand(): void
    {
        $profileName = 'default_article_translations_update';

        $fileName = 'articlestranslation.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 225.', $consoleOutput[3]);
    }

    public function testOrderCsvExportCommand(): void
    {
        $expectedLineAmount = 18;
        $profileName = 'default_orders_minimal';

        $fileName = 'order.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 17.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testMainOrderCsvExportCommand(): void
    {
        $expectedLineAmount = 5;
        $profileName = 'default_order_main_data';

        $fileName = 'mainorder.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 4.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testTranslationsCsvExportCommand(): void
    {
        $expectedLineAmount = 16;
        $profileName = 'default_system_translations';

        $fileName = 'translation.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 15.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testNewsletterCsvExportCommand(): void
    {
        $this->importNewsletterDemoData();

        $expectedLineAmount = 26;
        $profileName = 'default_newsletter_recipient';

        $fileName = 'newsletter.csv';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: csv.', $consoleOutput[1]);
        static::assertSame('Total count: 25.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 6462;
        $profileName = 'default_articles';

        $fileName = 'article.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 100 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 100.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testVariantsXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 6462;
        $profileName = 'default_articles';

        $fileName = 'variants.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf(
            'sw:importexport:export -p %s -l 100 -x %s',
            $profileName,
            $fileName
        ));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 100.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testCustomerXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 43;
        $profileName = 'default_customers';

        $fileName = 'customer.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 1 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 1.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testCategoriesXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 208;
        $profileName = 'default_categories_minimal';

        $fileName = 'categories.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 40 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 40.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsInStockXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 1408;
        $profileName = 'default_article_in_stock';

        $fileName = 'articlesinstock.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 200 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 200.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsPricesXmlExportCommandWithLimit(): void
    {
        if (SwagVersionHelper::isShopware578()) {
            $expectedLineAmount = 1308;
        } else {
            $expectedLineAmount = 1208;
        }
        $profileName = 'default_article_prices';

        $fileName = 'articlesprices.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 100 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 100.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testProductsImagesXmlExportCommandWithLimit(): void
    {
        $profileName = 'default_article_images';

        $fileName = 'articlesimage.xml';

        $this->expectException(\InvalidArgumentException::class);
        $this->runCommand(sprintf('sw:importexport:export -p %s -l 50 %s', $profileName, $fileName));
    }

    public function testProductsTranslationsXmlExportCommandWithLimit(): void
    {
        $profileName = 'default_article_translations_update';

        $fileName = 'articlestranslation.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s %s', $profileName, $fileName));

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 225.', $consoleOutput[3]);
    }

    public function testMainOrderXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 111;
        $profileName = 'default_order_main_data';

        $fileName = 'mainorder.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 2 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 2.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testTranslationsXmlExportCommandWithLimit(): void
    {
        $expectedLineAmount = 88;
        $profileName = 'default_system_translations';

        $fileName = 'translation.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 10 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 10.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testNewsletterXmlExportCommandWithLimit(): void
    {
        $this->importNewsletterDemoData();

        $expectedLineAmount = 230;
        $profileName = 'default_newsletter_recipient';

        $fileName = 'newsletter.xml';
        $this->addCreatedExportFile($fileName);

        $consoleOutput = $this->runCommand(sprintf('sw:importexport:export -p %s -l 15 %s', $profileName, $fileName));

        $lineAmount = $this->getLineAmount($fileName);

        static::assertSame('Using format: xml.', $consoleOutput[1]);
        static::assertSame('Total count: 15.', $consoleOutput[3]);
        static::assertSame(
            $expectedLineAmount,
            $lineAmount,
            sprintf('Expected %s lines, found %s', $expectedLineAmount, $lineAmount)
        );
    }

    public function testEmptyExportHasFileSizeInDatabaseCommand(): void
    {
        $profileName = 'default_orders';

        $fileName = 'empty.xml';
        $this->addCreatedExportFile($fileName);

        $this->runCommand(sprintf('sw:importexport:export -p %s --dateFrom "10-05-2015" --dateTo "15-05-2015" %s', $profileName, $fileName));
        $filePath = $this->getFilePath($fileName);
        $fileSize = (int) filesize($filePath);

        $connection = $this->getContainer()->get(Connection::class);
        $dbFileSize = (int) $connection->fetchOne('SELECT file_size FROM s_import_export_session ORDER BY id DESC');

        static::assertSame($fileSize, $dbFileSize);
    }

    private function getLineAmount(string $fileName): int
    {
        $fp = \file($this->getFilePath($fileName));
        static::assertIsArray($fp);

        return \count($fp);
    }
}
