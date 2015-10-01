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

//    /**
//     * @dataProvider writeProvider
//     */
//    public function testWrite($data, $expectedInsertedRows)
//    {
//        $this->write($data, $expectedInsertedRows);
//
//        $queryTable = $this->getDatabaseTester()->getConnection()->createQueryTable(
//                $this->dbTable, 'SELECT * FROM ' . $this->dbTable
//        );
////        echo $queryTable->__toString();
//    }
//
//    public function writeProvider()
//    {
//        return static::getDataProvider('testWrite');
//    }

//    /**
//     * @dataProvider insertOneProvider
//     */
//    public function testInsertOne($category, $expectedRow)
//    {
//        $this->insertOne($category, $expectedRow);
//    }
//
//    public function insertOneProvider()
//    {
//        return static::getDataProvider('testInsertOne');
//    }
//
//    /**
//     * @dataProvider updateOneProvider
//     */
//    public function testUpdateOne($category, $expectedRow)
//    {
//        $this->updateOne($category, $expectedRow);
//    }
//
//    public function updateOneProvider()
//    {
//        return static::getDataProvider('testUpdateOne');
//    }
}
