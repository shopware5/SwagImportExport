<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class CategoriesDbAdapterTest extends ImportExportTestHelper
{

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                dirname(__FILE__) . "/categories.yml"
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
        $dataFactory = $this->Plugin()->getDataFactory();

        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $rawData = $catDbAdapter->read($ids, $columns);

        foreach ($expected as $key1 => $value) {
            foreach ($value as $key2 => $val) {
                $this->assertEquals($rawData[$key1][$key2], $val);
            }
        }
        $this->assertEquals(count($rawData), $expectedCount);
    }

    public function readProvider()
    {
        $test1 = array(
            'c.id, c.parentId, c.name, c.active',
            array(3, 5, 6, 8, 15),
            array(
                2 => array(
                    'name' => 'Sommerwelten'
                )
            ),
            4
        );
        $test2 = array(
            'c.id, c.parentId, c.name, c.active',
            array(6, 8),
            array(
                0 => array(
                    'id' => 6
                ),
                1 => array(
                    'name' => 'Wohnwelten',
                )
            ),
            2
        );
        $test3 = array('c.id, c.active', array(3, 15), array(0 => array('id' => 3)), 1);

        return array(
            $test1, $test2, $test3
        );
    }

    /**
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expectedCount)
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $ids = $catDbAdapter->readRecordIds($start, $limit);
        $this->assertEquals($expectedCount, count($ids));
    }

    public function readRecordIdsProvider()
    {
        return array(
            array(0, 6, 4),
            array(1, 2, 2),
            array(0, 3, 3),
        );
    }

    public function testDefaultColumns()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $columns = $catDbAdapter->getDefaultColumns();

        $this->assertTrue(is_array($columns));
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite($data, $expectedInsertedRows)
    {
        $beforeTestCount = $this->getDatabaseTester()->getConnection()->getRowCount('s_categories');
        
        $dataFactory = $this->Plugin()->getDataFactory();

        $catDbAdapter = $dataFactory->createDbAdapter('categories');
        $catDbAdapter->write($data);

        $afterTestCount = $this->getDatabaseTester()->getConnection()->getRowCount('s_categories');
        
        $this->assertEquals($expectedInsertedRows, $afterTestCount - $beforeTestCount);
    }

    public function writeProvider()
    {
        return array(
            array(
                array(
                    array(
                        'id' => 15,
                        'parentId' => 3,
                        'name' => 'Test',
                    ),
                ),
                1
            ),
            array(
                array(
                    array(
                        'id' => 15,
                        'parentId' => 3,
                        'name' => 'Test',
                    ),
                    array(
                        'id' => 19,
                        'parentId' => 15,
                        'name' => 'Test 2',
                    ),
                ),
                2
            ),
        );
    }

}
