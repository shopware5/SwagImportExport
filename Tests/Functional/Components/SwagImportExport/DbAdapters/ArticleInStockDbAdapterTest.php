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
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticleInStockDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function test_write_should_update_article_stock()
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
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedArticleInStock = $dbalConnection->executeQuery("SELECT * FROM s_articles_details WHERE orderNumber='SW10004'")->fetchAll();

        $this->assertEquals(3, $updatedArticleInStock[0]['instock']);
    }

    public function test_write_with_invalid_order_number_throws_exception()
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

    public function test_read_and_read_records_should_get_same_result_count()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
        $filter = [
            'stockFilter' => 'notInStock',
        ];

        $preparedExportData = $articleInStockDbAdapter->readRecordIds(null, null, $filter);
        $exportedData = $articleInStockDbAdapter->read($preparedExportData, $this->getReadColumns());

        $this->assertCount(count($preparedExportData), $exportedData['default']);
    }

    public function test_read()
    {
        $articleInStockDbAdapter = $this->createArticlesInStockAbAdapter();
        $ids = [3];
        $result = $articleInStockDbAdapter->read($ids, $this->getReadColumns());

        $this->assertArrayHasKey('orderNumber', $result['default'][0], 'Could not fetch order number.');
        $this->assertArrayHasKey('inStock', $result['default'][0], 'Could not fetch article stock.');
        $this->assertArrayHasKey('name', $result['default'][0], 'Could not fetch article name.');
        $this->assertArrayHasKey('additionalText', $result['default'][0], 'Could not fetch additional test.');
        $this->assertArrayHasKey('supplier', $result['default'][0], 'Could not fetch supplier.');
        $this->assertArrayHasKey('price', $result['default'][0], 'Could not fetch article price.');
        $this->assertArrayHasKey('taxInput', $result['default'][0], 'Could not fetch tax id.');
        $this->assertArrayHasKey('tax', $result['default'][0], 'Could not fetch tax rate.');
    }

    public function test_read_should_throw_exception_if_ids_are_empty()
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
        return new ArticlesInStockDbAdapter();
    }

    /**
     * @return array
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
