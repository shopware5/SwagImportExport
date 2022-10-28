<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use Doctrine\DBAL\Connection;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\DbAdapterTestHelper;

class ProductsPricesDbAdapterTest extends DbAdapterTestHelper
{
    protected string $yamlFile = 'TestCases/articlePricesDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();
        $this->dbAdapter = DataDbAdapter::PRODUCT_PRICE_ADAPTER;
        $this->dbTable = ProfileDataProvider::PRODUCTS_PRICES_TABLE;
    }

    /**
     * @param array<string>               $columns
     * @param array<int>                  $ids
     * @param array<array<string, mixed>> $expected
     *
     * @dataProvider readProvider
     */
    public function testRead(array $columns, array $ids, array $expected, int $expectedCount): void
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    /**
     * @return array<string, array{columns: array<string>, ids: array<int>, expected: array<array<string, mixed>>, expectedCount: int}>
     */
    public function readProvider(): array
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @dataProvider readRecordIdsProvider
     *
     * @param list<int> $expected
     */
    public function testReadRecordIds(int $start, int $limit, array $expected, int $expectedCount): void
    {
        $this->readRecordIds($start, $limit, [], $expected, $expectedCount);
    }

    /**
     * @return array<string, array{start: int, limit: int, expected: list<int>, expectedCount: int}>
     */
    public function readRecordIdsProvider(): array
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    public function testWriteGraduatedPrices(): void
    {
        $orderNumber = 'SW10003';
        $connection = $this->getContainer()->get(Connection::class);
        $sql = 'SELECT price.* FROM s_articles_prices AS price
                INNER JOIN s_articles_details variant ON price.articledetailsID = variant.id
                WHERE variant.ordernumber = :orderNumber
                ORDER BY price.from';
        $pricesBeforeUpdate = $connection->executeQuery($sql, ['orderNumber' => $orderNumber])->fetchAllAssociative();
        static::assertCount(1, $pricesBeforeUpdate);

        $recordWithFourGraduatedPrices = [
            'default' => [
                [
                    'orderNumber' => $orderNumber,
                    'price' => '12.95',
                    'priceGroup' => 'EK',
                    'from' => '11',
                    'to' => '20',
                    'pseudoPrice' => '0',
                    'purchasePrice' => '0',
                    'name' => 'Münsterländer Aperitif 16%',
                    'additionalText' => '',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'regulationPrice' => '0',
                ],
                [
                    'orderNumber' => $orderNumber,
                    'price' => '13.95',
                    'priceGroup' => 'EK',
                    'from' => '6',
                    'to' => '10',
                    'pseudoPrice' => '0',
                    'purchasePrice' => '0',
                    'name' => 'Münsterländer Aperitif 16%',
                    'additionalText' => '',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'regulationPrice' => '0',
                ],
                [
                    'orderNumber' => $orderNumber,
                    'price' => '11.95',
                    'priceGroup' => 'EK',
                    'from' => '21',
                    'to' => 'beliebig',
                    'pseudoPrice' => '0',
                    'purchasePrice' => '0',
                    'name' => 'Münsterländer Aperitif 16%',
                    'additionalText' => '',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'regulationPrice' => '0',
                ],
                [
                    'orderNumber' => $orderNumber,
                    'price' => '14.95',
                    'priceGroup' => 'EK',
                    'from' => '1',
                    'to' => '5',
                    'pseudoPrice' => '0',
                    'purchasePrice' => '0',
                    'name' => 'Münsterländer Aperitif 16%',
                    'additionalText' => '',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'regulationPrice' => '0',
                ],
            ],
        ];
        $this->write($recordWithFourGraduatedPrices, 3);
        $pricesAfterFirstUpdate = $connection->executeQuery($sql, ['orderNumber' => $orderNumber])->fetchAllAssociative();
        static::assertCount(4, $pricesAfterFirstUpdate);
        $from = 1;
        foreach ($pricesAfterFirstUpdate as $price) {
            static::assertSame($from, (int) $price['from']);
            $from = (int) $price['to'] + 1;
        }

        $recordWithTwoGraduatedPrices = [
            'default' => [
                [
                    'orderNumber' => 'SW10003',
                    'price' => '66.7',
                    'priceGroup' => 'EK',
                    'from' => '16',
                    'to' => 'beliebig',
                    'pseudoPrice' => '0',
                    'purchasePrice' => '0',
                    'name' => 'Münsterländer Aperitif 16%',
                    'additionalText' => '',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'regulationPrice' => '0',
                ],
                [
                    'orderNumber' => 'SW10003',
                    'price' => '77.7',
                    'priceGroup' => 'EK',
                    'from' => '1',
                    'to' => '15',
                    'pseudoPrice' => '0',
                    'purchasePrice' => '0',
                    'name' => 'Münsterländer Aperitif 16%',
                    'additionalText' => '',
                    'supplierName' => 'Feinbrennerei Sasse',
                    'regulationPrice' => '0',
                ],
            ],
        ];
        $this->write($recordWithTwoGraduatedPrices, -2);

        $pricesAfterSecondUpdate = $connection->executeQuery($sql, ['orderNumber' => $orderNumber])->fetchAllAssociative();
        static::assertCount(2, $pricesAfterSecondUpdate);
        $from = 1;
        foreach ($pricesAfterSecondUpdate as $price) {
            static::assertSame($from, (int) $price['from']);
            $from = (int) $price['to'] + 1;
        }
    }
}
