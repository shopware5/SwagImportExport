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
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ArticleTranslationUpdateProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldUpdateExistingArticleTranslation(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_translation_profile_update.csv';
        $expectedArticleName = 'Munsterland Aperitif 16% Super deluxe';
        $expectedArticleDescription = 'Test description';
        $expectedArticleLongDescription = 'Test description long';

        $this->runCommand("sw:import:import -p default_article_translations_update {$filePath}");

        $queryResult = $this->executeQuery(
            "SELECT * FROM s_core_translations as t JOIN s_articles_details as a ON t.objectkey = a.id AND t.objecttype = 'article' WHERE a.ordernumber = 'SW10003'",
            \PDO::FETCH_ASSOC
        );

        $article = $queryResult[0];
        $translations = \unserialize($article['objectdata']);

        static::assertEquals($expectedArticleName, $translations['txtArtikel']);
        static::assertEquals($expectedArticleDescription, $translations['txtshortdescription']);
        static::assertEquals($expectedArticleLongDescription, $translations['txtlangbeschreibung']);
    }
}
