<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\ArticlesInStockDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticleInStockDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteShouldUpdateArticleStock()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
        $updateInStockRecord = [
            'default' => [
                [
                    'orderNumber' => 'SW10004',
                    'inStock' => '3',
                ],
            ],
        ];
        $articleInStockDbAdapter->write($updateInStockRecord);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedArticleInStock = $dbalConnection->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='SW10004'")->fetchAll();

        static::assertEquals(3, $updatedArticleInStock[0]['instock']);
    }

    public function testWriteWithInvalidOrderNumberThrowsException()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
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
        $articleInStockDbAdapter->write($updateInStockRecord);
    }

    public function testReadAndReadRecordsShouldGetSameResultCount()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
        $filter = [
            'stockFilter' => 'notInStock',
        ];

        $preparedExportData = $articleInStockDbAdapter->readRecordIds(null, null, $filter);
        $exportedData = $articleInStockDbAdapter->read($preparedExportData, $this->getReadColumns());

        static::assertCount(\count($preparedExportData), $exportedData['default']);
    }

    public function testRead()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
        $ids = [3];
        $result = $articleInStockDbAdapter->read($ids, $this->getReadColumns());

        static::assertArrayHasKey('orderNumber', $result['default'][0], 'Could not fetch order number.');
        static::assertArrayHasKey('inStock', $result['default'][0], 'Could not fetch article stock.');
        static::assertArrayHasKey('name', $result['default'][0], 'Could not fetch article name.');
        static::assertArrayHasKey('additionalText', $result['default'][0], 'Could not fetch additional test.');
        static::assertArrayHasKey('supplier', $result['default'][0], 'Could not fetch supplier.');
        static::assertArrayHasKey('price', $result['default'][0], 'Could not fetch article price.');
        static::assertArrayHasKey('taxInput', $result['default'][0], 'Could not fetch tax id.');
        static::assertArrayHasKey('tax', $result['default'][0], 'Could not fetch tax rate.');
    }

    public function testReadShouldThrowExceptionIfIdsAreEmpty()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
        $columns = ['variant.number as orderNumber'];
        $ids = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Artikel ohne IDs nicht lesen.');
        $articleInStockDbAdapter->read($ids, $columns);
    }

    /**
     * @return ArticlesInStockDbAdapter
     */
    private function createArticlesInStockAbAdapter()
    {
        return $this->getContainer()->get(ArticlesInStockDbAdapter::class);
    }

    /**
     * @return array<string>
     */
    private function getReadColumns()
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
