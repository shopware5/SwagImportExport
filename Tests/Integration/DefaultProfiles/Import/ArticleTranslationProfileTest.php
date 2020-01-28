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

class ArticleTranslationProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;

    public function test_import_should_create_article_translation()
    {
        $filePath = __DIR__ . '/_fixtures/article_translation_profile.csv';
        $expectedArticleName = 'Boomerang deluxe';
        $expectedArticleDescription = 'My test description';

        $this->runCommand("sw:import:import -p default_article_translations {$filePath}");

        $queryResult = $this->executeQuery(
            "SELECT * FROM s_core_translations as t JOIN s_articles_details AS a ON t.objectkey = a.articleID AND t.objecttype = 'article' WHERE a.ordernumber = 'SW10236'",
            \PDO::FETCH_ASSOC
        );

        $article = $queryResult[0];
        $translations = unserialize($article['objectdata']);

        static::assertEquals($expectedArticleName, $translations['txtArtikel']);
        static::assertEquals($expectedArticleDescription, $translations['txtshortdescription']);
    }
}
