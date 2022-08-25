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
use SwagImportExport\Components\DbAdapters\ProductsInStockDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ProductInStockDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteShouldUpdateProductStock(): void
    {
        $productInStockDbAdapter = $this->createProductsInStockAbAdapter();
        $updateInStockRecord = [
            'default' => [
                [
                    'orderNumber' => 'SW10004',
                    'inStock' => '3',
                ],
            ],
        ];
        $productInStockDbAdapter->write($updateInStockRecord);

        $updatedProductInStock = $this->getContainer()->get('dbal_connection')->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='SW10004'")->fetchAllAssociative();

        static::assertEquals(3, $updatedProductInStock[0]['instock']);
    }

    public function testWriteWithInvalidOrderNumberThrowsException(): void
    {
        $productsInStockAbAdapter = $this->createProductsInStockAbAdapter();
        $updateInStockRecord = [
            'default' => [
                [
                    'orderNumber' => 'SW-999999',
                    'inStock' => '3',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Artikel mit Nummer SW-999999 existiert nicht.');
        $productsInStockAbAdapter->write($updateInStockRecord);
    }

    public function testReadAndReadRecordsShouldGetSameResultCount(): void
    {
        $productsInStockAbAdapter = $this->createProductsInStockAbAdapter();
        $filter = [
            'stockFilter' => 'notInStock',
        ];

        $preparedExportData = $productsInStockAbAdapter->readRecordIds(null, null, $filter);
        $exportedData = $productsInStockAbAdapter->read($preparedExportData, $this->getReadColumns());

        static::assertCount(\count($preparedExportData), $exportedData['default']);
    }

    public function testRead(): void
    {
        $productsInStockAbAdapter = $this->createProductsInStockAbAdapter();
        $ids = [3];
        $result = $productsInStockAbAdapter->read($ids, $this->getReadColumns());

        static::assertArrayHasKey('orderNumber', $result['default'][0], 'Could not fetch order number.');
        static::assertArrayHasKey('inStock', $result['default'][0], 'Could not fetch article stock.');
        static::assertArrayHasKey('name', $result['default'][0], 'Could not fetch article name.');
        static::assertArrayHasKey('additionalText', $result['default'][0], 'Could not fetch additional test.');
        static::assertArrayHasKey('supplier', $result['default'][0], 'Could not fetch supplier.');
        static::assertArrayHasKey('price', $result['default'][0], 'Could not fetch article price.');
        static::assertArrayHasKey('taxInput', $result['default'][0], 'Could not fetch tax id.');
        static::assertArrayHasKey('tax', $result['default'][0], 'Could not fetch tax rate.');
    }

    public function testReadShouldThrowExceptionIfIdsAreEmpty(): void
    {
        $productsInStockAbAdapter = $this->createProductsInStockAbAdapter();
        $columns = ['variant.number as orderNumber'];
        $ids = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Artikel ohne IDs nicht lesen.');
        $productsInStockAbAdapter->read($ids, $columns);
    }

    private function createProductsInStockAbAdapter(): ProductsInStockDbAdapter
    {
        return $this->getContainer()->get(ProductsInStockDbAdapter::class);
    }

    /**
     * @return array<string>
     */
    private function getReadColumns(): array
    {
        return [
            'variant.number as orderNumber',
            'variant.inStock as inStock',
            'article.name as name',
            'variant.additionalText as additionalText',
            'articleSupplier.name as supplier',
            'prices.price as price',
        ];
    }
}
