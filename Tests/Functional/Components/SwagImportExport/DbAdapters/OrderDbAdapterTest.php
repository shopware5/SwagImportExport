<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\OrdersDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class OrderDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function test_write_should_be_valid()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();
        $validOrderRecords = $this->getValidDemoRecordsForWriteTest();

        $ordersDbAdapter->write($validOrderRecords);
    }

    public function test_write_should_throw_exception_with_invalid_array()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $invalidRecords = [
            'defaults' => [],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Bestellungen gefunden.');
        $ordersDbAdapter->write($invalidRecords);
    }

    public function test_write_without_orderDetailId_should_use_number_instead()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        unset($records['default'][0]['orderDetailId']);

        $ordersDbAdapter->write($records);
    }

    public function test_write_with_not_existing_orderDetailId_should_throw_an_exception()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['orderDetailId'] = 999999;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bestellposition mit ID 999999 nicht gefunden');
        $ordersDbAdapter->write($records);
    }

    public function test_write_with_not_existing_status_id_should_throw_an_exception()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['status'] = 123;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Status 123 was not found for order 20001.');
        $ordersDbAdapter->write($records);
    }

    public function test_write_with_invalid_status_id_should_throw_an_exception()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['status'] = 'abc';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('status Feld muss int sein!');
        $ordersDbAdapter->write($records);
    }

    public function test_write_with_invalid_payment_status_should_throw_an_exception()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['cleared'] = null;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The identifier id is missing for a query of Shopware\Models\Order\Status');
        $ordersDbAdapter->write($records);
    }

    public function test_write_with_empty_status_should_be_valid()
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['status'] = null;

        $ordersDbAdapter->write($records);
    }

    /**
     * @return OrdersDbAdapter
     */
    private function createOrdersDbAdapter()
    {
        return new OrdersDbAdapter();
    }

    /**
     * @return array
     */
    private function getValidDemoRecordsForWriteTest()
    {
        $data = [
            'orderId' => 15,
            'number' => '20001',
            'customerId' => 2,
            'status' => 0,
            'cleared' => 17,
            'paymentId' => 4,
            'dispatchId' => 9,
            'partnerId' => null,
            'shopId' => 1,
            'invoiceAmount' => 998.56,
            'invoiceAmountNet' => 839.13,
            'invoiceShipping' => 0,
            'invoiceShippingNet' => 0,
            'orderTime' => '2012-08-30 10:15:54',
            'transactionId' => null,
            'comment' => null,
            'customerComment' => null,
            'internalComment' => null,
            'net' => 1,
            'taxFree' => 0,
            'temporaryId' => null,
            'referer' => null,
            'clearedDate' => null,
            'trackingCode' => null,
            'languageIso' => null,
            'currency' => 'EUR',
            'currencyFactor' => 1,
            'remoteAddress' => '127.0.0.1',
            'orderDetailId' => 42,
            'articleId' => 197,
            'taxId' => 1,
            'taxRate' => 19,
            'statusId' => 1,
            'articleNumber' => 'SW10196',
            'price' => 836.134,
            'quantity' => 1,
            'articleName' => 'ESD Download Artikel',
            'shipped' => 0,
            'shippedGroup' => 0,
            'releaseddate' => '0000-00-00',
            'mode' => 0,
            'esd' => 1,
            'config' => null,
        ];

        return [
            'default' => [
                $data,
            ],
        ];
    }
}
