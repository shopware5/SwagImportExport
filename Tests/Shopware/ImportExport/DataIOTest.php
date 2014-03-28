<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class DataIOTest extends ImportExportTestHelper
{

    public function testPreloadRecordIds()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        $dataIO = $dataFactory->getAdapter('categories', $postData);

        $dataIO->preloadRecordIds();
        
        $allIds = $dataIO->getRecordIds();
        
        $this->assertEquals(count($allIds), 62);
    }
    
    public function testRead()
    {
        $postData = array(
            'filter' => '',
            'limit' => array('limit' => 100, 'offset' => 0)
        );

        $dataFactory = $this->Plugin()->getDataFactory();

        $dataIO = $dataFactory->getAdapter('categories', $postData);

        $dataIO->preloadRecordIds();

        $rawData1 = $dataIO->read(11);
        $rawData2 = $dataIO->read(21);
        $rawData3 = $dataIO->read(55);

        $this->assertEquals(count($rawData1), 11);
        $this->assertEquals(count($rawData2), 21);
        $this->assertEquals(count($rawData3), 55);
    }

}
