<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\ArticlesTranslationsDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesTranslationsDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testReadShouldThrowExceptionIfIdsAreEmpty(): void
    {
        $articlesTranslationsDbAdapter = $this->getArticlesTranslationsDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Übersetzungen ohne IDs nicht lesen.');
        $articlesTranslationsDbAdapter->read([], []);
    }

    public function testReadShouldRespondCorrectTranslations(): void
    {
        $articlesTranslationsDbAdapter = $this->getArticlesTranslationsDbAdapter();
        $translations = $articlesTranslationsDbAdapter->read([151, 152], $articlesTranslationsDbAdapter->getDefaultColumns());

        static::assertArrayHasKey('default', $translations);
        static::assertEquals('Dart machine standard device', $translations['default'][0]['name']);
        static::assertEquals('Beach bag Sailor', $translations['default'][1]['name']);
    }

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty(): void
    {
        $articlesTranslationsDbAdapter = $this->getArticlesTranslationsDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Artikelübersetzungen gefunden.');
        $articlesTranslationsDbAdapter->write([]);
    }

    public function testWriteShouldUpdateArticleTranslationToDatabase(): void
    {
        $articlesTranslationsDbAdapter = $this->getArticlesTranslationsDbAdapter();
        $records = [
            'default' => [
                ['articleNumber' => 'SW10003', 'languageId' => 2, 'name' => 'My translation test'],
            ],
        ];

        $articlesTranslationsDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'")->fetchOne();

        $mainArticleTranslation = $dbalConnection->executeQuery("SELECT name FROM s_articles_translations WHERE articleID = '{$articleId}'")->fetchOne();
        $result = $dbalConnection->executeQuery("SELECT * from s_core_translations WHERE objectkey = '{$articleId}' AND objecttype = 'Article'")->fetch(\PDO::FETCH_ASSOC);
        $translation = \unserialize($result['objectdata']);

        static::assertEquals('My translation test', $mainArticleTranslation);
        static::assertEquals('My translation test', $translation['txtArtikel']);
    }

    private function getArticlesTranslationsDbAdapter(): ArticlesTranslationsDbAdapter
    {
        return $this->getContainer()->get(ArticlesTranslationsDbAdapter::class);
    }
}
