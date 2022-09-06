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

class CustomersDbAdapterTest extends DbAdapterTestHelper
{
    protected string $yamlFile = 'TestCases/customersDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = DataDbAdapter::CUSTOMER_ADAPTER;
        $this->dbTable = ProfileDataProvider::CUSTOMER_TABLE;
    }

    /**
     * @param int[] $ids
     *
     * @dataProvider readProvider
     */
    public function testRead(array $columns, array $ids, array $expected, int $expectedCount): void
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider(): array
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @param int[] $expectedIds
     *
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds(int $start, int $limit, array $expectedIds, int $expectedCount): void
    {
        $this->readRecordIds($start, $limit, [], $expectedIds, $expectedCount);
    }

    public function readRecordIdsProvider(): array
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    /**
     * @dataProvider writeWithEmptyFile
     *
     * @param array<string, mixed> $records
     */
    public function testWriteWithEmptyFile(array $records, int $expectedInsertedRows): void
    {
        $this->expectException(\Exception::class);

        $this->write($records, $expectedInsertedRows);
    }

    public function writeWithEmptyFile(): array
    {
        return $this->getDataProvider('writeWithEmptyFile');
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite(array $records, int $expectedInsertedRows): void
    {
        $this->write($records, $expectedInsertedRows);
    }

    public function writeProvider(): array
    {
        return $this->getDataProvider('testWrite');
    }
}
