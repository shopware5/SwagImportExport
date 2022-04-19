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
use Shopware\Components\SwagImportExport\DbAdapters\OrdersDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class OrdersDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testWriteShouldBeValid(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();
        $validOrderRecords = $this->getValidDemoRecordsForWriteTest();

        $ordersDbAdapter->write($validOrderRecords);

        static::assertTrue(true);
    }

    public function testWriteShouldThrowExceptionWithInvalidArray(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $invalidRecords = [
            'defaults' => [],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Bestellungen gefunden.');
        $ordersDbAdapter->write($invalidRecords);
    }

    public function testWriteWithoutOrderDetailIdShouldUseNumberInstead(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        unset($records['default'][0]['orderDetailId']);

        $ordersDbAdapter->write($records);

        static::assertTrue(true);
    }

    public function testWriteWithNotExistingOrderDetailIdShouldThrowAnException(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['orderDetailId'] = 999999;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Bestellposition mit ID 999999 nicht gefunden');
        $ordersDbAdapter->write($records);
    }

    public function testWriteWithNotExistingStatusIdShouldThrowAnException(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['status'] = 123;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Status 123 was not found for order 20001.');
        $ordersDbAdapter->write($records);
    }

    public function testWriteWithInvalidStatusIdShouldThrowAnException(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['status'] = 'abc';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('status Feld muss int sein und nicht abc!');
        $ordersDbAdapter->write($records);
    }

    public function testWriteWithInvalidPaymentStatusShouldThrowAnException(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['cleared'] = null;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The identifier id is missing for a query of Shopware\Models\Order\Status');
        $ordersDbAdapter->write($records);
    }

    public function testWriteWithEmptyStatusShouldBeValid(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();

        $records = $this->getValidDemoRecordsForWriteTest();
        $records['default'][0]['status'] = null;

        $ordersDbAdapter->write($records);

        static::assertTrue(true);
    }

    public function testRead(): void
    {
        $ordersDbAdapter = $this->createOrdersDbAdapter();
        $columns = $ordersDbAdapter->getDefaultColumns();
        $ids = [42, 43, 44];

        $exportedOrders = $ordersDbAdapter->read($ids, $columns);

        static::assertArrayHasKey('default', $exportedOrders);
        static::assertIsArray($exportedOrders['default']);
        static::assertArrayHasKey('customerNumber', $exportedOrders['default'][0]);
        static::assertArrayHasKey('deviceType', $exportedOrders['default'][0]);
        static::assertArrayHasKey('detailAttributeAttribute1', $exportedOrders['default'][0]);
    }

    private function createOrdersDbAdapter(): OrdersDbAdapter
    {
        return new OrdersDbAdapter();
    }

    /**
     * @return array{default: array<array<string, mixed>>}
     */
    private function getValidDemoRecordsForWriteTest(): array
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
