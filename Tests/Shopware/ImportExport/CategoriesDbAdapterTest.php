<?php

namespace Tests\Shopware\ImportExport;

use Tests\Helper\DbAdapterTest;

class CategoriesDbAdapterTest extends DbAdapterTest
{
    protected $yamlFile = "TestCases/categoriesDbAdapter.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdapter = 'categories';
        $this->dbTable = 's_categories';
    }

    /**
     * @param array $columns
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
     * @param $start
     * @param $limit
     * @param $expected
     * @param $expectedCount
     *
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expected, $expectedCount)
    {
        $this->readRecordIds($start, $limit, [], $expected, $expectedCount);
    }

    public function readRecordIdsProvider()
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    /**
     * @param $expectedColumns
     *
     * @dataProvider defaultColumnProvider
     */
    public function testDefaultColumns($expectedColumns)
    {
        $this->defaultColumns($expectedColumns, count($expectedColumns));
    }

    public function defaultColumnProvider()
    {
        return $this->getDataProvider('testDefaultColumns');
    }

    /**
     * @param array $data
     * @param int $expectedInsertedRows
     *
     * @dataProvider writeProvider
     */
    public function testWrite($data, $expectedInsertedRows)
    {
        $this->write($data, $expectedInsertedRows);
    }

    public function writeProvider()
    {
        return $this->getDataProvider('testWrite');
    }

    /**
     * @param array $category
     * @param array $expectedRow
     *
     * @dataProvider insertOneProvider
     */
    public function testInsertOne($category, $expectedRow)
    {
        $this->insertOne($category, $expectedRow);
    }

    public function insertOneProvider()
    {
        return $this->getDataProvider('testInsertOne');
    }

    /**
     * @dataProvider updateOneProvider
     */
    public function testUpdateOne($category, $expectedRow)
    {
        $this->updateOne($category, $expectedRow);
    }

    public function updateOneProvider()
    {
        return $this->getDataProvider('testUpdateOne');
    }
}
