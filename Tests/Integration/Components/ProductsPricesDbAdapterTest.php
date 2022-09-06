<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

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
}
