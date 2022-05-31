<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DbAdapterTestHelper;

class CustomersDbAdapterTest extends DbAdapterTestHelper
{
    use ContainerTrait;

    protected string $yamlFile = 'TestCases/customersDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = 'customers';
        $this->dbTable = 's_user';
    }

    /**
     * @param int[] $ids
     *
     * @dataProvider readProvider
     */
    public function testRead(array $columns, array $ids, array $expected, int $expectedCount)
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    /**
     * @return array
     */
    public function readProvider()
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @param int[] $expectedIds
     *
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds(int $start, int $limit, array $expectedIds, int $expectedCount)
    {
        $this->readRecordIds($start, $limit, [], $expectedIds, $expectedCount);
    }

    /**
     * @return array
     */
    public function readRecordIdsProvider()
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    /**
     * @dataProvider writeWithEmptyFile
     *
     * @param array<string, mixed> $records
     */
    public function testWriteWithEmptyFile(array $records, int $expectedInsertedRows)
    {
        self::expectException(\Exception::class);

        $this->write($records, $expectedInsertedRows);
    }

    /**
     * @return array
     */
    public function writeWithEmptyFile()
    {
        return $this->getDataProvider('writeWithEmptyFile');
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite(array $records, int $expectedInsertedRows)
    {
        $this->write($records, $expectedInsertedRows);
    }

    /**
     * @return array
     */
    public function writeProvider()
    {
        return $this->getDataProvider('testWrite');
    }
}
