<?php

namespace Tests\Shopware\ImportExport;

class MainOrdersDbAdapterTest extends DbAdapterTest
{
    /**
     * @var string
     */
    protected static $yamlFile = "TestCases/mainOrdersDbAdapter.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdapter = 'mainOrders';
        $this->dbTable = 's_order';
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_YamlDataSet
     */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . "/Database/mainOrders.yml"
        );
    }

    /**
     * @param $start
     * @param $limit
     * @param $filter
     * @param $expectedIds
     * @param $expectedCount
     *
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $filter, $expectedIds, $expectedCount)
    {
        $this->readRecordIds($start, $limit, $filter, $expectedIds, $expectedCount);
    }

    /**
     * @return mixed
     */
    public function readRecordIdsProvider()
    {
        return static::getDataProvider('testReadRecordIds');
    }

    /**
     * @param $columns
     * @param $ids
     * @param $expected
     * @param $expectedCount
     * @param $section
     *
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected, $expectedCount, $section)
    {
        $this->read($columns, $ids, $expected, $expectedCount, $section);
    }

    /**
     * @return mixed
     */
    public function readProvider()
    {
        return static::getDataProvider('testRead');
    }

    /**
     * @param $columns
     * @param $ids
     * @param $expected
     * @param $expectedCount
     *
     * @expectedException \Exception
     * @dataProvider readColumnsExceptionProvider
     */
    public function testReadColumnsException($columns, $ids, $expected, $expectedCount)
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    /**
     * @return mixed
     */
    public function readColumnsExceptionProvider()
    {
        return static::getDataProvider('testReadColumnsException');
    }

    /**
     * @param $columns
     * @param $ids
     * @param $expected
     * @param $expectedCount
     *
     * @expectedException \Exception
     * @dataProvider readColumnsExceptionProvider
     */
    public function testReadIdsException($columns, $ids, $expected, $expectedCount)
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    /**
     * @return mixed
     */
    public function readIdsExceptionProvider()
    {
        return static::getDataProvider('testReadIdsException');
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

    /**
     * @return mixed
     */
    public function defaultColumnsProvider()
    {
        return static::getDataProvider('testDefaultColumns');
    }

    /**
     * @param $records
     * @param $expectedInsertedRows
     *
     * @expectedException \Exception
     * @dataProvider writeProvider
     */
    public function testWrite($records, $expectedInsertedRows)
    {
        $this->write($records, $expectedInsertedRows);
    }

    /**
     * @return mixed
     */
    public function writeProvider()
    {
        return static::getDataProvider('testWrite');
    }
}
