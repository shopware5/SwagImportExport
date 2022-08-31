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
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ProductDefaultProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldUpdateExistingProduct(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_profile_update.csv';
        $expectedProductName = 'Münsterländer Aperitif Update';

        $this->runCommand("sw:import:import -p default_articles {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedProductName}'");

        static::assertEquals('Updated description', $updatedProduct[0]['description']);
        static::assertEquals($expectedProductName, $updatedProduct[0]['name']);
    }

    public function testImportShouldCreateProductAndSupplier(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_profile_create.csv';
        $expectedName = 'New Created Article';

        $this->runCommand("sw:import:import -p default_articles {$filePath}");

        $createdProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedName}'");
        $createdMainVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE articleID='{$createdProduct[0]['id']}'");
        $createdSupplier = $this->executeQuery("SELECT * FROM s_articles_supplier WHERE id='{$createdProduct[0]['supplierID']}'");

        static::assertEquals('This is my brand-new product', $createdProduct[0]['description']);
        static::assertEquals('test9999', $createdMainVariant[0]['ordernumber']);
        static::assertEquals($expectedName, $createdProduct[0]['name']);
        static::assertEquals('New Supplier', $createdSupplier[0]['name']);
    }

    public function testImportShouldCreateVariant(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_profile_create_variant.csv';
        $expectedProductName = 'Some long drink';

        $this->runCommand("sw:import:import -p default_articles {$filePath}");

        $createdProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedProductName}'");
        $createdVariants = $this->executeQuery("SELECT * FROM s_articles_details WHERE articleID='{$createdProduct[0]['id']}' ORDER BY ordernumber");

        static::assertEquals($expectedProductName, $createdProduct[0]['name']);
        static::assertEquals('Description of the main variant', $createdProduct[0]['description_long']);

        static::assertEquals('test-100.1', $createdVariants[0]['ordernumber']);
        static::assertEquals('0,5 Liter', $createdVariants[0]['additionaltext']);
        static::assertEquals('Flasche(n)', $createdVariants[0]['packunit']);

        static::assertEquals('test-100.2', $createdVariants[1]['ordernumber']);
        static::assertEquals('1,5 Liter', $createdVariants[1]['additionaltext']);
        static::assertEquals('Flasche(n)', $createdVariants[1]['packunit']);
    }
}
