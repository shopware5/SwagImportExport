<?php

namespace Tests\Shopware\ImportExport;

use Tests\Helper\DbAdapterTest;

class MainOrdersDbAdapterTest extends DbAdapterTest
{
    /**
     * @var string
     */
    protected $yamlFile = "TestCases/mainOrdersDbAdapter.yml";

    public function setUp()
    {
        $this->markTestIncomplete('Needs to be completely revised.');

        parent::setUp();

        $this->dbAdapter = 'mainOrders';
        $this->dbTable = 's_order';
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
        return $this->getDataProvider('testReadRecordIds');
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
        return $this->getDataProvider('testRead');
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
        return $this->getDataProvider('testReadColumnsException');
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
        return $this->getDataProvider('testReadIdsException');
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
        return $this->getDataProvider('testDefaultColumns');
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
        return $this->getDataProvider('testWrite');
    }
}
