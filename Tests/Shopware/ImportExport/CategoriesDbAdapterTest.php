<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\DbAdapterTest;

class CategoriesDbAdapterTest extends DbAdapterTest
{
    protected static $yamlFile = "TestCases/categoriesDbAdaptor.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdaptor = 'categories';
        $this->dbTable = 's_categories';
    }

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                dirname(__FILE__) . "/Database/categories.yml"
        );
    }

//    public function testRawData()
//    {
//        $dataFactory = $this->Plugin()->getDataFactory();
//        
//        
//        
//        $profile = $this->Plugin()->getProfileFactory()->getProfileSerialized()->readProfile($params);
//        $dataIO = $dataFactory->getDataIO($profile->getType(), $params);
//        
//        
//                
//        $dataIO->loadSession();
//        
//                
//        $dataIO->read(100);
//        $dataIO->read(50);
//                
//        
//              
//        
//        
//        
//        
//        $catergoriesDbAdapter = $dataFactory->createCategoriesDbAdapter();
//        
//        $rawData = $catergoriesDbAdapter->read(array(1,2,3));
//
//        $this->assertEquals(count($rawData), 62);
//    }

    /**
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
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expectedCount)
    {
        $this->readRecordIds($start, $limit, $expectedCount);
    }

    public function readRecordIdsProvider()
    {
        return static::getDataProvider('testReadRecordIds');
    }

    public function testDefaultColumns()
    {
        $this->defaultColumns();
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite($data, $expectedInsertedRows)
    {
        $this->write($data, $expectedInsertedRows);
    }

    public function writeProvider()
    {
        return static::getDataProvider('testWrite');
    }

    /**
     * @dataProvider insertOneProvider
     */
    public function testInsertOne($category, $expectedRow)
    {
        $this->insertOne($category, $expectedRow);
    }

    public function insertOneProvider()
    {
        return static::getDataProvider('testInsertOne');
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
        return static::getDataProvider('testUpdateOne');
    }

}
