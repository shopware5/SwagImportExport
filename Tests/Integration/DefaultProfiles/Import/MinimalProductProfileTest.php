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

class MinimalProductProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldCreateProduct(): void
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_profile_create.csv';
        $expectedProductName = 'Test Article';

        $this->runCommand("sw:import:import -p default_articles_minimal {$filePath}");

        $createdProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedProductName}'");

        static::assertEquals($expectedProductName, $createdProduct[0]['name']);
    }

    public function testImportShouldCreateSupplier(): void
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_profile_create.csv';
        $expectedSupplierName = 'New Supplier';
        $expectedProductName = 'Test Article';

        $this->runCommand("sw:import:import -p default_articles_minimal {$filePath}");

        $createdProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedProductName}'");
        $createdSupplier = $this->executeQuery("SELECT * FROM s_articles_supplier WHERE id={$createdProduct[0]['supplierID']}");

        static::assertEquals($expectedProductName, $createdProduct[0]['name']);
        static::assertEquals($expectedSupplierName, $createdSupplier[0]['name']);
    }
}
