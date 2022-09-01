<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\MainOrdersDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class MainOrdersDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testReadWithEmptyIdsArrayThrowsException(): void
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $ids = [];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Bestellungen ohne IDs nicht auslesen.');
        $mainOrdersDbAdapter->read($ids, $columns);
    }

    public function testReadWithEmptyColumnsArrayThrowsException(): void
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = [];
        $ids = [1, 2, 3];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann Bestellungen ohne Spaltennamen nicht auslesen.');
        $mainOrdersDbAdapter->read($ids, $columns);
    }

    public function testReadShouldCreateValidColumns(): void
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $ids = [15, 57];

        $exportedOrders = $mainOrdersDbAdapter->read($ids, $columns);

        static::assertArrayHasKey('order', $exportedOrders, 'Could not fetch orders.');
        static::assertArrayHasKey('orderId', $exportedOrders['order'][0], 'Could not fetch order id.');
        static::assertArrayHasKey('invoiceAmount', $exportedOrders['order'][0], 'Could not fetch amount.');
        static::assertArrayHasKey('clearedDate', $exportedOrders['order'][0], 'Could not fetch clear date.');
        static::assertArrayHasKey('taxRateSum', $exportedOrders, 'Could not fetch tax rates.');
        static::assertArrayHasKey('orderId', $exportedOrders['taxRateSum'][0], 'Could not fetch order id.');
        static::assertArrayHasKey('taxRateSums', $exportedOrders['taxRateSum'][0], 'Could not fetch tax sum.');
        static::assertArrayHasKey('taxRate', $exportedOrders['taxRateSum'][0], 'Could not fetch tax rate.');
    }

    public function testReadShouldExportCorrectResult(): void
    {
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();

        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $ids = [15];

        $exportedOrders = $mainOrdersDbAdapter->read($ids, $columns);

        /* Check order details */
        static::assertSame('15', $exportedOrders['order'][0]['orderId']);
        static::assertSame('20001', $exportedOrders['order'][0]['orderNumber']);
        static::assertSame('998.56', $exportedOrders['order'][0]['invoiceAmount']);
        static::assertSame('839.13', $exportedOrders['order'][0]['invoiceAmountNet']);

        /* Check order tax details */
        static::assertSame('15', $exportedOrders['taxRateSum'][0]['orderId']);
        static::assertSame('159.44', $exportedOrders['taxRateSum'][0]['taxRateSums']);
        static::assertSame('19', $exportedOrders['taxRateSum'][0]['taxRate']);
    }

    public function testReadShouldExportCorrectResultWithoutTaxId(): void
    {
        // Set the tax ID and rate of a single order detail to null and zero, respectively
        $orderDetailId = 44;
        $db = Shopware()->Container()->get('db');
        $db->query(
            'UPDATE s_order_details
            SET taxID = NULL, tax_rate = 0
            WHERE id = :orderDetailId',
            [
                'orderDetailId' => $orderDetailId,
            ]
        );

        $ids = [15];
        $mainOrdersDbAdapter = $this->createMainOrdersDbAdapter();
        $columns = $mainOrdersDbAdapter->getDefaultColumns();
        $exportedOrders = $mainOrdersDbAdapter->read($ids, $columns);

        // Check order details
        static::assertSame('15', $exportedOrders['order'][0]['orderId']);
        static::assertSame('20001', $exportedOrders['order'][0]['orderNumber']);
        static::assertSame('998.56', $exportedOrders['order'][0]['invoiceAmount']);
        static::assertSame('839.13', $exportedOrders['order'][0]['invoiceAmountNet']);

        // Check order tax details
        static::assertCount(2, $exportedOrders['taxRateSum']);
        static::assertSame('15', $exportedOrders['taxRateSum'][0]['orderId']);
        static::assertSame('0', $exportedOrders['taxRateSum'][0]['taxRate']);
        static::assertSame('0', $exportedOrders['taxRateSum'][0]['taxRateSums']);
        static::assertSame('15', $exportedOrders['taxRateSum'][1]['orderId']);
        static::assertSame('19', $exportedOrders['taxRateSum'][1]['taxRate']);
        static::assertSame('158.49', $exportedOrders['taxRateSum'][1]['taxRateSums']);

        // Revert the changes made to the order detail
        $db->query(
            'UPDATE s_order_details
            SET taxID = 1, tax_rate = 19
            WHERE id = :orderDetailId',
            [
                'orderDetailId' => $orderDetailId,
            ]
        );
    }

    private function createMainOrdersDbAdapter(): MainOrdersDbAdapter
    {
        return new MainOrdersDbAdapter();
    }
}
