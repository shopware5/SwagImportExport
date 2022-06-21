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

class MinimalArticleVariantsProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldInsertNewVariant(): void
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_variants_profile.csv';
        $expectedVariantOrderNumber = 'SW10002.4';
        $expectedArticleName = 'Münsterländer Lagerkorn 32%';

        $this->runCommand("sw:import:import -p default_article_variants_minimal {$filePath}");

        $importedVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedVariantOrderNumber}'");
        $importedArticle = $this->executeQuery("SELECT * FROM s_articles WHERE id='{$importedVariant[0]['articleID']}'");

        static::assertEquals($expectedVariantOrderNumber, $importedVariant[0]['ordernumber']);
        static::assertEquals($expectedArticleName, $importedArticle[0]['name']);
    }

    public function testImportShouldInsertNewArticleWithVariant(): void
    {
        $filePath = __DIR__ . '/_fixtures/minimal_article_variants_profile.csv';
        $expectedVariantOrderNumber = 'ordernumber.2';
        $expectedArticleName = 'Test Artikel';

        $this->runCommand("sw:import:import -p default_article_variants_minimal {$filePath}");

        $importedVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedVariantOrderNumber}'");
        $importedArticle = $this->executeQuery("SELECT * FROM s_articles WHERE id='{$importedVariant[0]['articleID']}'");

        static::assertEquals($expectedVariantOrderNumber, $importedVariant[0]['ordernumber']);
        static::assertEquals($expectedArticleName, $importedArticle[0]['name']);
    }
}
