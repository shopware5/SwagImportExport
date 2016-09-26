<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Tests\Helper\DbAdapterTest;

class CustomersDbAdapterTest extends DbAdapterTest
{
    protected $yamlFile = "TestCases/customersDbAdapter.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdapter = 'customers';
        $this->dbTable = 's_user';
    }

    /**
     * @param string $columns
     * @param int[] $ids
     * @param array $expected
     * @param int $expectedCount
     *
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected, $expectedCount)
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider()
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @param int $start
     * @param array $limit
     * @param int[] $expectedIds
     * @param int $expectedCount
     *
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expectedIds, $expectedCount)
    {
        $this->readRecordIds($start, $limit, [], $expectedIds, $expectedCount);
    }

    public function readRecordIdsProvider()
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    /**
     * @param $records
     * @param $expectedInsertedRows
     *
     * @dataProvider writeWithEmptyFile
     * @expectedException \Exception
     */
    public function testWriteWithEmptyFile($records, $expectedInsertedRows)
    {
        $this->write($records, $expectedInsertedRows);
    }

    public function writeWithEmptyFile()
    {
        return $this->getDataProvider('testWriteWithEmptyFile');
    }

    /**
     * @param array $records
     * @param int $expectedInsertedRows
     *
     * @dataProvider writeProvider
     */
    public function testWrite($records, $expectedInsertedRows)
    {
        $this->write($records, $expectedInsertedRows);
    }

    public function writeProvider()
    {
        return $this->getDataProvider('testWrite');
    }
}
