<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class DataIOTest extends ImportExportTestHelper
{
    
    public function getPostData()
    {
        return array(
            'filter' => '',
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'csv'
        );
    }
    
    
    public function testPreloadRecordIds()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        $dataIO = $dataFactory->getAdapter('categories', $postData);

        $dataIO->preloadRecordIds();

        $allIds = $dataIO->getRecordIds();

        $this->assertEquals(count($allIds), 62);
    }

    public function testCategoriesRead()
    {
        $postData = $this->getPostData();

        $dataFactory = $this->Plugin()->getDataFactory();

        $dataIO = $dataFactory->getAdapter('categories', $postData);

        $dataIO->preloadRecordIds();

        $rawData1 = $dataIO->read(11);
        $rawData2 = $dataIO->read(21);
        $rawData3 = $dataIO->read(255);

        $this->assertEquals(count($rawData1), 11);
        $this->assertEquals(count($rawData2), 21);
        $this->assertEquals(count($rawData3), 40);
    }
    
    public function testSessionState()
    {
        $postData = $this->getPostData();

        $dataFactory = $this->Plugin()->getDataFactory();
        
        $dataIO = $dataFactory->getAdapter('categories', $postData);

        $this->assertEquals($dataIO->getSessionState(), 'new');
    }
    
//    public function testStartSession()
//    {
//        $postData = $this->getPostData();
//
//        $dataFactory = $this->Plugin()->getDataFactory();
//        
//        $dataIO = $dataFactory->getAdapter('categories', $postData);
//
//        $dataIO->startSession();
//    }
    
    
//    public function testArticlesRead()
//    {
//        $postData = array(
//            'filter' => '',
//            'limit' => array('limit' => 140, 'offset' => 0)
//        );
//
//        $dataFactory = $this->Plugin()->getDataFactory();
//
//        $dataIO = $dataFactory->getAdapter('articles', $postData);
//
//        $dataIO->preloadRecordIds();
//
//        $rawData1 = $dataIO->read(11);
//        $rawData2 = $dataIO->read(21);
//        $rawData3 = $dataIO->read(255);
//
//        $this->assertEquals(count($rawData1), 11);
//        $this->assertEquals(count($rawData2), 21);
//        $this->assertEquals(count($rawData3), 140);
//    }

}
