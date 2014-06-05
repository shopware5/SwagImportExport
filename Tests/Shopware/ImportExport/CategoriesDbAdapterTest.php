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

    public function testRead()
    {
        $columns = 'c.id, c.parentId, c.name, c.active';

        $ids = array(3, 5, 6, 8, 15);

        $dataFactory = $this->Plugin()->getDataFactory();

        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $rawData = $catDbAdapter->read($ids, $columns);

        $this->assertEquals($rawData[2]['name'], 'Sommerwelten');
        $this->assertEquals(count($rawData), 4);
    }

    public function testReadRecordIds()
    {
        $start = 0;
        $limit = 6;

        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $ids = $catDbAdapter->readRecordIds($start, $limit);
        $this->assertEquals(count($ids), 4);

        $allIds = $catDbAdapter->readRecordIds();
        $this->assertEquals(count($allIds), 4);
    }

    public function testDefaultColumns()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $columns = $catDbAdapter->getDefaultColumns();

        $this->assertTrue(is_array($columns));
    }

}
