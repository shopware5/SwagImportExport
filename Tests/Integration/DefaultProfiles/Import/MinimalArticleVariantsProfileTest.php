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

class MinimalArticleVariantsProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_import_should_insert_new_variant()
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_variants_profile.csv';
        $expectedVariantOrderNumber = 'SW10002.4';
        $expectedArticleName = 'Münsterländer Lagerkorn 32%';

        $this->runCommand("sw:import:import -p default_article_variants_minimal {$filePath}");

        $importedVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedVariantOrderNumber}'");
        $importedArticle = $this->executeQuery("SELECT * FROM s_articles WHERE id='{$importedVariant[0]['articleID']}'");

        $this->assertEquals($expectedVariantOrderNumber, $importedVariant[0]['ordernumber']);
        $this->assertEquals($expectedArticleName, $importedArticle[0]['name']);
    }

    public function test_import_should_insert_new_article_with_variant()
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_variants_profile.csv';
        $expectedVariantOrderNumber = 'ordernumber.2';
        $expectedArticleName = 'Test Artikel';

        $this->runCommand("sw:import:import -p default_article_variants_minimal {$filePath}");

        $importedVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedVariantOrderNumber}'");
        $importedArticle = $this->executeQuery("SELECT * FROM s_articles WHERE id='{$importedVariant[0]['articleID']}'");

        $this->assertEquals($expectedVariantOrderNumber, $importedVariant[0]['ordernumber']);
        $this->assertEquals($expectedArticleName, $importedArticle[0]['name']);
    }
}
