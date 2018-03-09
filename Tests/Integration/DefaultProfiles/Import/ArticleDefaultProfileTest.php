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

class ArticleDefaultProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_update_existing_article()
    {
        $filePath = __DIR__ . '/_fixtures/article_profile_update.csv';
        $expectedArticleName = 'Münsterländer Aperitif Update';

        $this->runCommand("sw:import:import -p default_articles {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedArticleName}'");

        $this->assertEquals('Updated description', $updatedArticle[0]['description']);
        $this->assertEquals($expectedArticleName, $updatedArticle[0]['name']);
    }

    public function test_import_should_create_article_and_supplier()
    {
        $filePath = __DIR__ . '/_fixtures/article_profile_create.csv';
        $expectedName = 'New Created Article';

        $this->runCommand("sw:import:import -p default_articles {$filePath}");

        $createdArticle = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedName}'");
        $createdMainVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE articleID='{$createdArticle[0]['id']}'");
        $createdSupplier = $this->executeQuery("SELECT * FROM s_articles_supplier WHERE id='{$createdArticle[0]['supplierID']}'");

        $this->assertEquals('This is my brand new article', $createdArticle[0]['description']);
        $this->assertEquals('test9999', $createdMainVariant[0]['ordernumber']);
        $this->assertEquals($expectedName, $createdArticle[0]['name']);
        $this->assertEquals('New Supplier', $createdSupplier[0]['name']);
    }

    public function test_import_should_create_variant()
    {
        $filePath = __DIR__ . '/_fixtures/article_profile_create_variant.csv';
        $expectedArticleName = 'Some long drink';

        $this->runCommand("sw:import:import -p default_articles {$filePath}");

        $createdArticle = $this->executeQuery("SELECT * FROM s_articles WHERE name='{$expectedArticleName}'");
        $createdVariants = $this->executeQuery("SELECT * FROM s_articles_details WHERE articleID='{$createdArticle[0]['id']}' ORDER BY ordernumber");

        $this->assertEquals($expectedArticleName, $createdArticle[0]['name']);
        $this->assertEquals('Description of the main variant', $createdArticle[0]['description_long']);

        $this->assertEquals('test-100.1', $createdVariants[0]['ordernumber']);
        $this->assertEquals('0,5 Liter', $createdVariants[0]['additionaltext']);
        $this->assertEquals('Flasche(n)', $createdVariants[0]['packunit']);

        $this->assertEquals('test-100.2', $createdVariants[1]['ordernumber']);
        $this->assertEquals('1,5 Liter', $createdVariants[1]['additionaltext']);
        $this->assertEquals('Flasche(n)', $createdVariants[1]['packunit']);
    }
}
