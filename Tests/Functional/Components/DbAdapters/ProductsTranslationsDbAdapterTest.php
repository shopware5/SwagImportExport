<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\ProductsTranslationsDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ProductsTranslationsDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testReadShouldThrowExceptionIfIdsAreEmpty(): void
    {
        $productsTranslationsDbAdapter = $this->getProductsTranslationsDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Übersetzungen ohne IDs nicht lesen.');
        $productsTranslationsDbAdapter->read([], []);
    }

    public function testReadShouldRespondCorrectTranslations(): void
    {
        $productsTranslationsDbAdapter = $this->getProductsTranslationsDbAdapter();
        $translations = $productsTranslationsDbAdapter->read([151, 152], $productsTranslationsDbAdapter->getDefaultColumns());

        static::assertArrayHasKey('default', $translations);
        static::assertEquals('Dart machine standard device', $translations['default'][0]['name']);
        static::assertEquals('Beach bag Sailor', $translations['default'][1]['name']);
    }

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty(): void
    {
        $productsTranslationsDbAdapter = $this->getProductsTranslationsDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Artikelübersetzungen gefunden.');
        $productsTranslationsDbAdapter->write([]);
    }

    public function testWriteShouldUpdateProductTranslationToDatabase(): void
    {
        $productsTranslationsDbAdapter = $this->getProductsTranslationsDbAdapter();
        $records = [
            'default' => [
                ['articleNumber' => 'SW10003', 'languageId' => 2, 'name' => 'My translation test'],
            ],
        ];

        $productsTranslationsDbAdapter->write($records);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'")->fetchOne();

        $mainProductTranslation = $dbalConnection->executeQuery("SELECT name FROM s_articles_translations WHERE articleID = '{$productId}'")->fetchOne();
        $result = $dbalConnection->executeQuery("SELECT * from s_core_translations WHERE objectkey = '{$productId}' AND objecttype = 'Article'")->fetchAssociative();
        static::assertIsArray($result);
        $translation = \unserialize($result['objectdata']);

        static::assertEquals('My translation test', $mainProductTranslation);
        static::assertEquals('My translation test', $translation['txtArtikel']);
    }

    private function getProductsTranslationsDbAdapter(): ProductsTranslationsDbAdapter
    {
        return $this->getContainer()->get(ProductsTranslationsDbAdapter::class);
    }
}
