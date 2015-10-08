<?php

namespace Tests\Shopware\ImportExport;

class CustomersDbAdapterTest extends DbAdapterTest
{
    protected static $yamlFile = "TestCases/customersDbAdapter.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdapter = 'customers';
        $this->dbTable = 's_user';
    }

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/Database/customers.yml'
        );
    }

    /**
     * @param $columns
     * @param $ids
     * @param $expected
     * @param $expectedCount
     *
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected, $expectedCount)
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider()
    {
        return static::getDataProvider('testRead');
    }

    /**
     * @param $start
     * @param $limit
     * @param $expectedIds
     * @param $expectedCount
     *
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expectedIds, $expectedCount)
    {
        $this->readRecordIds($start, $limit, $expectedIds, $expectedCount);
    }

    public function readRecordIdsProvider()
    {
        return static::getDataProvider('testReadRecordIds');
    }

    /**
     * @param $expectedColumns
     * @param $expectedCount
     *
     * @dataProvider defaultColumnsProvider
     */
    public function testDefaultColumns($expectedColumns, $expectedCount)
    {
        $this->defaultColumns($expectedColumns, $expectedCount);
    }

    public function defaultColumnsProvider()
    {
        return static::getDataProvider('testDefaultColumns');
    }

    //TODO: uncomment after merging PT-2436
//    /**
//     * @param $records
//     * @param $expectedInsertedRows
//     *
//     * @dataProvider writeWithEmptyFile
//     * @expectedException \Exception
//     */
//    public function testWriteWithEmptyFile($records, $expectedInsertedRows)
//    {
//        $this->write($records, $expectedInsertedRows);
//    }
//
//    public function writeWithEmptyFile()
//    {
//        return static::getDataProvider('testWriteWithEmptyFile');
//    }

    /**
     * @param $records
     * @param $expectedInsertedRows
     *
     * @dataProvider writeProvider
     */
    public function testWrite($records, $expectedInsertedRows)
    {
        $this->write($records, $expectedInsertedRows);
    }

    public function writeProvider()
    {
        return static::getDataProvider('testWrite');
    }
}
