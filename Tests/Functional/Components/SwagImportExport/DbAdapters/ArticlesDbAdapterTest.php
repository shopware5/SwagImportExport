<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function test_write_should_throw_exception_if_records_are_empty()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Keine Artikel gefunden.');
        $articlesDbAdapter->write([]);
    }

    public function test_new_article_should_be_written_to_database()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $newArticleRecord = [
            'article' => [
                [
                    'name' => 'Testartikel',
                    'orderNumber' => 'SW-99999',
                    'mainNumber' => 'SW-99999',
                    'supplierId' => 2,
                    'supplierName' => 'Feinbrennerei Sasse',
                    'taxId' => 1,
                    'purchasePrice' => 10,
                ],
            ],
        ];
        $articlesDbAdapter->write($newArticleRecord);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdArticleDetail = $dbalConnection->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='SW-99999'")->fetchAll();
        $createdArticle = $dbalConnection->executeQuery("SELECT a.* FROM s_articles as a JOIN s_articles_details as d ON d.articleID = a.id WHERE d.orderNumber='SW-99999'")->fetchAll();

        static::assertEquals($newArticleRecord['article'][0]['name'], $createdArticle[0]['name']);
        static::assertEquals($newArticleRecord['article'][0]['taxId'], $createdArticle[0]['taxID']);
        static::assertEquals($newArticleRecord['article'][0]['orderNumber'], $createdArticleDetail[0]['ordernumber']);
        static::assertEquals($newArticleRecord['article'][0]['purchasePrice'], $createdArticleDetail[0]['purchaseprice']);
    }

    public function test_write_should_assign_new_similar_articles()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();

        $articleRecordWithNewSimilars = [
            'article' => [
                [
                    'name' => 'Münsterländer Aperitif 16%',
                    'orderNumber' => 'SW10003',
                    'mainNumber' => 'SW10003',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'taxId' => 1,
                ],
            ],
            'similar' => $this->getSimilarArticles(),
        ];

        $articlesDbAdapter->write($articleRecordWithNewSimilars);
        $unprocessedData = $articlesDbAdapter->getUnprocessedData();

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $sql = "SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'";
        $articleId = $dbalConnection->executeQuery($sql)->fetch(\PDO::FETCH_COLUMN);
        $createdArticleSimilars = $dbalConnection->executeQuery('SELECT * FROM s_articles_similar WHERE articleID = ?', [$articleId])->fetchAll();

        static::assertEmpty($unprocessedData);
        static::assertEquals(7, $createdArticleSimilars[4]['relatedarticle']);
    }

    public function test_write_should_add_supplier_if_it_not_exists()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();

        $articleRecordWithNewSupplier = [
            'article' => [
                [
                    'name' => 'Testartikel',
                    'orderNumber' => 'SW-99999',
                    'mainNumber' => 'SW-99999',
                    'supplierName' => 'Test Supplier',
                    'taxId' => 1,
                ],
            ],
        ];
        $articlesDbAdapter->write($articleRecordWithNewSupplier);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $sql = "SELECT * FROM s_articles_supplier WHERE name='Test Supplier'";
        $createdArticleSupplier = $dbalConnection->executeQuery($sql)->fetchAll();

        static::assertEquals($articleRecordWithNewSupplier['article']['supplerName'], $createdArticleSupplier[0]['supplierName']);
    }

    public function test_write_article_without_supplier_throws_exception()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $articleWithoutSupplier = [
            'article' => [
                [
                    'name' => 'Testartikel',
                    'orderNumber' => 'SW-99999',
                    'mainNumber' => 'SW-99999',
                    'taxId' => 1,
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hersteller für den Artikel SW-99999 nicht gefunden.');
        $articlesDbAdapter->write($articleWithoutSupplier);
    }

    public function test_write_should_assign_new_accessory_articles()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();

        $articleRecordWithNewSimilars = [
            'article' => [
                [
                    'name' => 'Münsterländer Aperitif 16%',
                    'orderNumber' => 'SW10003',
                    'mainNumber' => 'SW10003',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'taxId' => 1,
                ],
            ],
            'accessory' => [
                [
                    'accessoryId' => '4',
                    'parentIndexElement' => 0,
                ],
                [
                    'accessoryId' => '10',
                    'parentIndexElement' => 0,
                ],
            ],
        ];

        $articlesDbAdapter->write($articleRecordWithNewSimilars);
        $unprocessedData = $articlesDbAdapter->getUnprocessedData();

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'")->fetch(\PDO::FETCH_COLUMN);
        $createdArticleSimilars = $dbalConnection->executeQuery('SELECT * FROM s_articles_relationships WHERE articleID = ?', [$articleId])->fetchAll();

        static::assertEmpty($unprocessedData);
        static::assertEquals(4, $createdArticleSimilars[1]['relatedarticle']);
    }

    public function test_new_image_should_fill_unprocessed_data_array()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $records = [
            'article' => [
                0 => [
                    'orderNumber' => 'SW10006',
                    'mainNumber' => 'SW10006',
                ],
            ],
            'image' => [
                0 => [
                    'imageUrl' => 'file://' . realpath(__DIR__) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png',
                    'parentIndexElement' => 0,
                ],
            ],
        ];
        $articlesDbAdapter->write($records);
        $unprocessedData = $articlesDbAdapter->getUnprocessedData();

        static::assertNotEmpty($unprocessedData);
        static::assertEquals($records['article'][0]['orderNumber'], $unprocessedData['articlesImages']['default'][0]['ordernumber']);
        static::assertEquals($records['image'][0]['imageUrl'], $unprocessedData['articlesImages']['default'][0]['image']);
    }

    public function test_existing_image_should_be_added_to_article()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $records = [
            'article' => [
                0 => [
                    'orderNumber' => 'SW10006',
                    'mainNumber' => 'SW10006',
                ],
            ],
            'image' => [
                0 => [
                    'mediaId' => 6,
                    'path' => 'Muensterlaender_Lagerkorn',
                    'description' => 'testimport1',
                    'parentIndexElement' => 0,
                ],
            ],
        ];
        $articlesDbAdapter->write($records);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10006'")->fetch(\PDO::FETCH_COLUMN);
        $image = $dbalConnection->executeQuery("SELECT * FROM s_articles_img WHERE img = 'Muensterlaender_Lagerkorn' AND articleID = ?", [$articleId])->fetch(\PDO::FETCH_ASSOC);

        static::assertEquals($articleId, $image['articleID']);
        static::assertEquals($records['image'][0]['mediaId'], $image['media_id']);
        static::assertEquals($records['image'][0]['description'], $image['description']);
        static::assertEquals('jpg', $image['extension']);
    }

    public function test_write_article_variant_should_be_written_to_database()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $newArticleWithVariantRecord = [
            'article' => [
                [
                    'name' => 'Test Artikel',
                    'orderNumber' => 'ordernumber.1',
                    'mainNumber' => 'ordernumber.1',
                    'supplierName' => 'shopware AG',
                    'taxId' => 1,
                ],
                [
                    'name' => 'Test Artikel',
                    'orderNumber' => 'ordernumber.2',
                    'mainNumber' => 'ordernumber.1',
                    'supplierName' => 'shopware AG',
                    'taxId' => 1,
                ],
            ],
        ];
        $articlesDbAdapter->write($newArticleWithVariantRecord);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdArticleVariantDetail = $dbalConnection->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='ordernumber.2'")->fetchAll();
        $createdArticleVariant = $dbalConnection->executeQuery("SELECT * FROM s_articles WHERE id='{$createdArticleVariantDetail[0]['articleID']}'")->fetchAll();

        static::assertEquals($newArticleWithVariantRecord['article'][1]['name'], $createdArticleVariant[0]['name']);
        static::assertEquals($newArticleWithVariantRecord['article'][1]['taxId'], $createdArticleVariant[0]['taxID']);
        static::assertEquals($newArticleWithVariantRecord['article'][1]['orderNumber'], $createdArticleVariantDetail[0]['ordernumber']);
    }

    public function test_write_article_variant_with_not_existing_main_number_throws_exception()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $articleWithoutExistingMainNumber = [
            'article' => [
                [
                    'name' => 'Test Artikel',
                    'orderNumber' => 'ordernumber.2',
                    'mainNumber' => 'not-existing-main-number',
                    'supplierName' => 'shopware AG',
                    'taxId' => 1,
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Variante/Artikel mit Nummer not-existing-main-number nicht gefunden.');
        $articlesDbAdapter->write($articleWithoutExistingMainNumber);
    }

    public function test_write_article_with_attribute_translation_should_be_written_to_database()
    {
        $attributeService = Shopware()->Container()->get('shopware_attribute.crud_service');
        /* @var CrudService $attributeService */
        $attributeService->update('s_articles_attributes', 'mycustomfield', 'string', ['translatable' => true]);

        $articlesDbAdapter = $this->createArticleDbAdapter();
        $records = [
            'article' => [
                0 => [
                    'orderNumber' => 'SW10006',
                    'mainNumber' => 'SW10006',
                ],
            ],
            'translation' => [
                0 => [
                    'languageId' => 2,
                    'mycustomfield' => 'my custom translation',
                    'parentIndexElement' => 0,
                ],
            ],
        ];
        $articlesDbAdapter->write($records);

        $attributeService->delete('s_articles_attributes', 'mycustomfield');
        Shopware()->Container()->get('shopware.cache_manager')->clearOpCache();
        Shopware()->Container()->get('shopware.cache_manager')->clearProxyCache();

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10006'")->fetch(\PDO::FETCH_COLUMN);
        $result = $dbalConnection->executeQuery("SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey={$articleId}")->fetchAll();
        $importedTranslation = unserialize($result[0]['objectdata']);

        // trait rollback not working - so we rollback manually
        $dbalConnection->executeQuery("DELETE FROM s_core_translations WHERE objecttype='article' AND objectkey={$articleId}");
        $dbalConnection->executeQuery("DELETE FROM s_articles_translations WHERE articleID={$articleId}");

        static::assertEquals($records['translation'][0]['mycustomfield'], $importedTranslation['__attribute_mycustomfield']);
    }

    public function test_read()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $ids = [3];
        $result = $articlesDbAdapter->read($ids, $this->getColumns());

        static::assertArrayHasKey('article', $result, 'Could not fetch articles.');
        static::assertArrayHasKey('price', $result, 'Could not fetch article prices.');
        static::assertArrayHasKey('image', $result, 'Could not fetch article image prices.');
        static::assertArrayHasKey('propertyValue', $result, 'Could not fetch article property values.');
        static::assertArrayHasKey('similar', $result, 'Could not fetch similar articles.');
        static::assertArrayHasKey('accessory', $result, 'Could not fetch accessory articles.');
        static::assertArrayHasKey('category', $result, 'Could not fetch categories.');
        static::assertArrayHasKey('translation', $result, 'Could not fetch article translations.');
        static::assertArrayHasKey('configurator', $result, 'Could not fetch article configurators');
    }

    public function test_read_should_throw_exception_if_ids_are_empty()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $columns = ['article' => 'article.id as articleId'];
        $ids = [];

        $this->expectException(\Exception::class);
        $articlesDbAdapter->read($ids, $columns);
    }

    public function test_read_should_throw_exception_if_columns_are_empty()
    {
        $articlesDbAdapter = $this->createArticleDbAdapter();
        $columns = [];
        $ids = [1, 2, 3];

        $this->expectException(\Exception::class);
        $articlesDbAdapter->read($ids, $columns);
    }

    /**
     * @return ArticlesDbAdapter
     */
    private function createArticleDbAdapter()
    {
        return new ArticlesDbAdapter();
    }

    /**
     * @return array
     */
    private function getColumns()
    {
        return [
            'article' => [
                'article.id as articleId',
            ],
            'price' => [
                'prices.articleDetailsId as variantId',
            ],
            'image' => [
                'images.id as id',
            ],
            'propertyValues' => [
                'article.id as articleId',
            ],
            'similar' => [
                'similar.id as similarId',
            ],
            'accessory' => [
                'accessory.id as accessoryId',
            ],
            'configurator' => [
                'variant.id as variantId',
            ],
            'category' => [
                'categories.id as categoryId',
            ],
            'translation' => [
                'article.id as articleId',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getSimilarArticles()
    {
        return [
            [
                'similarId' => '4',
                'parentIndexElement' => 0,
            ],
            [
                'similarId' => '2',
                'parentIndexElement' => 0,
            ],
            [
                'similarId' => '5',
                'parentIndexElement' => 0,
            ],
            [
                'similarId' => '6',
                'parentIndexElement' => 0,
            ],
            [
                'similarId' => '7',
                'parentIndexElement' => 0,
            ],
        ];
    }
}
