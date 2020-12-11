<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesTranslationsDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesTranslationsDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function test_read_should_throw_exception_if_ids_are_empty()
    {
        $articlesTranslationsDbAdapter = $this->createArticlesTranslationsDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Übersetzungen ohne IDs nicht lesen.');
        $articlesTranslationsDbAdapter->read([], []);
    }

    public function test_read_should_respond_correct_translations()
    {
        $articlesTranslationsDbAdapter = $this->createArticlesTranslationsDbAdapter();
        $translations = $articlesTranslationsDbAdapter->read([151, 152], $articlesTranslationsDbAdapter->getDefaultColumns());

        static::assertArrayHasKey('default', $translations);
        static::assertEquals('Dart machine standard device', $translations['default'][0]['name']);
        static::assertEquals('Beach bag Sailor', $translations['default'][1]['name']);
    }

    public function test_write_should_throw_exception_if_records_are_empty()
    {
        $articlesTranslationsDbAdapter = $this->createArticlesTranslationsDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Artikelübersetzungen gefunden.');
        $articlesTranslationsDbAdapter->write([]);
    }

    public function test_write_should_update_article_translation_to_database()
    {
        $articlesTranslationsDbAdapter = $this->createArticlesTranslationsDbAdapter();
        $records = [
            'default' => [
                ['articleNumber' => 'SW10003', 'languageId' => 2, 'name' => 'My translation test'],
            ],
        ];

        $articlesTranslationsDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'")->fetch(\PDO::FETCH_COLUMN);

        $mainArticleTranslation = $dbalConnection->executeQuery("SELECT name FROM s_articles_translations WHERE articleID = '{$articleId}'")->fetch(\PDO::FETCH_COLUMN);
        $result = $dbalConnection->executeQuery("SELECT * from s_core_translations WHERE objectkey = '{$articleId}' AND objecttype = 'Article'")->fetch(\PDO::FETCH_ASSOC);
        $translation = \unserialize($result['objectdata']);

        static::assertEquals('My translation test', $mainArticleTranslation);
        static::assertEquals('My translation test', $translation['txtArtikel']);
    }

    /**
     * @return ArticlesTranslationsDbAdapter
     */
    private function createArticlesTranslationsDbAdapter()
    {
        return new ArticlesTranslationsDbAdapter();
    }
}
