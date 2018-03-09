<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class MinimalArticleProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_create_article()
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_profile_create.csv';
        $expectedArticleName = 'Test Article';

        $this->runCommand("sw:import:import -p default_articles_minimal {$filePath}");

        $createdArticle = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedArticleName}'");

        $this->assertEquals($expectedArticleName, $createdArticle[0]['name']);
    }

    public function test_import_should_create_supplier()
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_profile_create.csv';
        $expectedSupplierName = 'New Supplier';
        $expectedArticleName = 'Test Article';

        $this->runCommand("sw:import:import -p default_articles_minimal {$filePath}");

        $createdArticle = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedArticleName}'");
        $createdSupplier = $this->executeQuery("SELECT * FROM s_articles_supplier WHERE id={$createdArticle[0]['supplierID']}");

        $this->assertEquals($expectedArticleName, $createdArticle[0]['name']);
        $this->assertEquals($expectedSupplierName, $createdSupplier[0]['name']);
    }
}
