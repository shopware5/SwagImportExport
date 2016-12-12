<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Shopware\Components\SwagImportExport\DbAdapters\MainOrdersDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class MainOrdersDbAdapterTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;

    /**
     * @return MainOrdersDbAdapter
     */
    private function createMainOrdersDbAdapter()
    {
        return new MainOrdersDbAdapter();
    }

    public function test_read_with_empty_ids_array_throws_exception()
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $ids = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Kann Bestellungen ohne IDs nicht auslesen.");
        $mainOrdersDbAdapter->read($ids, $columns);
    }

    public function test_read_with_empty_columns_array_throws_exception()
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = [];
        $ids = [ 1, 2, 3 ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Kann Bestellungen ohne Spaltennamen nicht auslesen.");
        $mainOrdersDbAdapter->read($ids, $columns);
    }

    public function test_read_should_create_valid_columns()
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $ids = [ 15, 57 ];

        $exportedOrders = $mainOrdersDbAdapter->read($ids, $columns);

        $this->assertArrayHasKey('order', $exportedOrders, 'Could not fetch orders.');
        $this->assertArrayHasKey('orderId', $exportedOrders['order'][0], 'Could not fetch order id.');
        $this->assertArrayHasKey('invoiceAmount', $exportedOrders['order'][0], 'Could not fetch amount.');
        $this->assertArrayHasKey('taxRateSum', $exportedOrders, 'Could not fetch tax rates.');
        $this->assertArrayHasKey('orderId', $exportedOrders['taxRateSum'][0], 'Could not fetch order id.');
        $this->assertArrayHasKey('taxRateSums', $exportedOrders['taxRateSum'][0], 'Could not fetch tax sum.');
        $this->assertArrayHasKey('taxRate', $exportedOrders['taxRateSum'][0], 'Could not fetch tax rate.');
    }

    public function test_read_should_export_correct_result()
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $ids = [ 15 ];

        $exportedOrders = $mainOrdersDbAdapter->read($ids, $columns);

        /*Check order details*/
        $this->assertEquals($exportedOrders['order'][0]['orderId'], 15);
        $this->assertEquals($exportedOrders['order'][0]['orderNumber'], '20001');
        $this->assertEquals($exportedOrders['order'][0]['invoiceAmount'], 998.56);
        $this->assertEquals($exportedOrders['order'][0]['invoiceAmountNet'], 839.13);

        /*Check order tax details*/
        $this->assertEquals($exportedOrders['taxRateSum'][0]['orderId'], 15);
        $this->assertEquals($exportedOrders['taxRateSum'][0]['taxRateSums'], 159.44);
        $this->assertEquals($exportedOrders['taxRateSum'][0]['taxRate'], 19);
    }
}