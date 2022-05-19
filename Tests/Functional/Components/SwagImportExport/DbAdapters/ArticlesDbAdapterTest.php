<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesDbAdapterTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Keine Artikel gefunden.');
        $productDbAdapter->write([]);
    }

    public function testNewProductShouldBeWrittenToDatabase(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $newProductRecord = [
            'article' => [
                [
                    'name' => 'Testartikel',
                    'orderNumber' => 'SW-99999',
                    'mainNumber' => 'SW-99999',
                    'supplierId' => 2,
                    'supplierName' => 'Feinbrennerei Sasse',
                    'taxId' => '1',
                    'purchasePrice' => '10',
                ],
            ],
        ];
        $productDbAdapter->write($newProductRecord);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdProductVariant = $dbalConnection->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='SW-99999'")->fetchAll();
        $createdProduct = $dbalConnection->executeQuery("SELECT a.* FROM s_articles as a JOIN s_articles_details as d ON d.articleID = a.id WHERE d.orderNumber='SW-99999'")->fetchAll();

        static::assertSame($newProductRecord['article'][0]['name'], $createdProduct[0]['name']);
        static::assertSame($newProductRecord['article'][0]['taxId'], $createdProduct[0]['taxID']);
        static::assertSame($newProductRecord['article'][0]['orderNumber'], $createdProductVariant[0]['ordernumber']);
        static::assertSame($newProductRecord['article'][0]['purchasePrice'], $createdProductVariant[0]['purchaseprice']);
    }

    public function testWriteShouldAssignNewSimilarProducts(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();

        $productRecordWithNewSimilar = [
            'article' => [
                [
                    'name' => 'Münsterländer Aperitif 16%',
                    'orderNumber' => 'SW10003',
                    'mainNumber' => 'SW10003',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'taxId' => 1,
                ],
            ],
            'similar' => $this->getSimilarProducts(),
        ];

        $productDbAdapter->write($productRecordWithNewSimilar);
        $unprocessedData = $productDbAdapter->getUnprocessedData();

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $sql = "SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'";
        $productId = $dbalConnection->executeQuery($sql)->fetch(\PDO::FETCH_COLUMN);
        $createdProductSimilar = $dbalConnection->executeQuery('SELECT * FROM s_articles_similar WHERE articleID = ?', [$productId])->fetchAll();

        static::assertEmpty($unprocessedData);
        static::assertSame('7', $createdProductSimilar[4]['relatedarticle']);
    }

    public function testWriteShouldAddSupplierIfItNotExists(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();

        $productRecordWithNewSupplier = [
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
        $productDbAdapter->write($productRecordWithNewSupplier);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $sql = "SELECT * FROM s_articles_supplier WHERE name='Test Supplier'";
        $createdProductSupplier = $dbalConnection->executeQuery($sql)->fetchAll();

        static::assertSame($productRecordWithNewSupplier['article'][0]['supplierName'], $createdProductSupplier[0]['name']);
    }

    public function testWriteProductWithoutSupplierThrowsException(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $productWithoutSupplier = [
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
        $productDbAdapter->write($productWithoutSupplier);
    }

    public function testWriteShouldAssignNewAccessoryProducts(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();

        $productRecordWithNewAccessory = [
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

        $productDbAdapter->write($productRecordWithNewAccessory);
        $unprocessedData = $productDbAdapter->getUnprocessedData();

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10003'")->fetch(\PDO::FETCH_COLUMN);
        $createdProductAccessory = $dbalConnection->executeQuery('SELECT * FROM s_articles_relationships WHERE articleID = ?', [$productId])->fetchAll();

        static::assertEmpty($unprocessedData);
        static::assertSame('4', $createdProductAccessory[1]['relatedarticle']);
    }

    public function testNewImageShouldFillUnprocessedDataArray(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $records = [
            'article' => [
                0 => [
                    'orderNumber' => 'SW10006',
                    'mainNumber' => 'SW10006',
                ],
            ],
            'image' => [
                0 => [
                    'imageUrl' => 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png',
                    'parentIndexElement' => 0,
                ],
            ],
        ];
        $productDbAdapter->write($records);
        $unprocessedData = $productDbAdapter->getUnprocessedData();

        static::assertNotEmpty($unprocessedData);
        static::assertSame($records['article'][0]['orderNumber'], $unprocessedData['articlesImages']['default'][0]['ordernumber']);
        static::assertSame($records['image'][0]['imageUrl'], $unprocessedData['articlesImages']['default'][0]['image']);
    }

    public function testExistingImageShouldBeAddedToProduct(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $records = [
            'article' => [
                0 => [
                    'orderNumber' => 'SW10006',
                    'mainNumber' => 'SW10006',
                ],
            ],
            'image' => [
                0 => [
                    'mediaId' => '6',
                    'path' => 'Muensterlaender_Lagerkorn',
                    'description' => 'testimport1',
                    'parentIndexElement' => 0,
                ],
            ],
        ];
        $productDbAdapter->write($records);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10006'")->fetch(\PDO::FETCH_COLUMN);
        $image = $dbalConnection->executeQuery("SELECT * FROM s_articles_img WHERE img = 'Muensterlaender_Lagerkorn' AND articleID = ?", [$productId])->fetch(\PDO::FETCH_ASSOC);

        static::assertSame($productId, $image['articleID']);
        static::assertSame($records['image'][0]['mediaId'], $image['media_id']);
        static::assertSame($records['image'][0]['description'], $image['description']);
        static::assertSame('jpg', $image['extension']);
    }

    public function testWriteProductVariantShouldBeWrittenToDatabase(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $newProductWithVariantRecord = [
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
                    'taxId' => '1',
                ],
            ],
        ];
        $productDbAdapter->write($newProductWithVariantRecord);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdProductVariant = $dbalConnection->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='ordernumber.2'")->fetchAll();
        $createdProduct = $dbalConnection->executeQuery("SELECT * FROM s_articles WHERE id='{$createdProductVariant[0]['articleID']}'")->fetchAll();

        static::assertSame($newProductWithVariantRecord['article'][1]['name'], $createdProduct[0]['name']);
        static::assertSame($newProductWithVariantRecord['article'][1]['taxId'], $createdProduct[0]['taxID']);
        static::assertSame($newProductWithVariantRecord['article'][1]['orderNumber'], $createdProductVariant[0]['ordernumber']);
    }

    public function testWriteProductVariantWithNotExistingMainNumberThrowsException(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $productWithoutExistingMainNumber = [
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
        $productDbAdapter->write($productWithoutExistingMainNumber);
    }

    public function testWriteProductWithAttributeTranslationShouldBeWrittenToDatabase(): void
    {
        $modelManager = $this->getContainer()->get('models');
        $modelManager->rollback();

        $attributeService = $this->getContainer()->get('shopware_attribute.crud_service');
        $attributeService->update('s_articles_attributes', 'mycustomfield', 'string', ['translatable' => true]);

        $productDbAdapter = $this->getProductDbAdapter();
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
                    'name' => 'Test product',
                    'shippingTime' => 'Translated shipping time',
                    'mycustomfield' => 'my custom translation',
                    'parentIndexElement' => 0,
                ],
            ],
        ];
        $productDbAdapter->write($records);

        $attributeService->delete('s_articles_attributes', 'mycustomfield');
        $this->getContainer()->get('shopware.cache_manager')->clearOpCache();
        $this->getContainer()->get('shopware.cache_manager')->clearProxyCache();

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10006'")->fetch(\PDO::FETCH_COLUMN);
        $result = $dbalConnection->executeQuery("SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey={$productId}")->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        // trait rollback not working - so we roll back manually
        $dbalConnection->executeQuery("DELETE FROM s_core_translations WHERE objecttype='article' AND objectkey={$productId}");
        $dbalConnection->executeQuery("DELETE FROM s_articles_translations WHERE articleID={$productId}");

        static::assertSame($records['translation'][0]['mycustomfield'], $importedTranslation['__attribute_mycustomfield']);
        static::assertSame($records['translation'][0]['name'], $importedTranslation['txtArtikel']);
        static::assertSame($records['translation'][0]['shippingTime'], $importedTranslation['txtshippingtime']);

        $modelManager->beginTransaction();
    }

    public function testRead(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $ids = [3];
        $result = $productDbAdapter->read($ids, $this->getColumns());

        static::assertArrayHasKey('article', $result, 'Could not fetch products.');
        static::assertArrayHasKey('price', $result, 'Could not fetch product prices.');
        static::assertArrayHasKey('image', $result, 'Could not fetch product image prices.');
        static::assertArrayHasKey('propertyValue', $result, 'Could not fetch product property values.');
        static::assertArrayHasKey('similar', $result, 'Could not fetch similar products.');
        static::assertArrayHasKey('accessory', $result, 'Could not fetch accessory products.');
        static::assertArrayHasKey('category', $result, 'Could not fetch categories.');
        static::assertArrayHasKey('translation', $result, 'Could not fetch product translations.');
        static::assertArrayHasKey('configurator', $result, 'Could not fetch product configurators');
    }

    public function testReadShouldThrowExceptionIfIdsAreEmpty(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $columns = ['article' => 'article.id as articleId'];
        $ids = [];

        $this->expectException(\Exception::class);
        $productDbAdapter->read($ids, $columns);
    }

    public function testReadShouldThrowExceptionIfColumnsAreEmpty(): void
    {
        $productDbAdapter = $this->getProductDbAdapter();
        $columns = [];
        $ids = [1, 2, 3];

        $this->expectException(\Exception::class);
        $productDbAdapter->read($ids, $columns);
    }

    private function getProductDbAdapter(): ArticlesDbAdapter
    {
        return $this->getContainer()->get(ArticlesDbAdapter::class);
    }

    /**
     * @return array<string, array<string>>
     */
    private function getColumns(): array
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
     * @return array<array{similarId: string, parentIndexElement: int}>
     */
    private function getSimilarProducts(): array
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
