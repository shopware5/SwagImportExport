<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class CategoriesDbAdapterTest extends ImportExportTestHelper
{

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
        $columns = 'c.id, c.parent, c.description as name, c.active';

        $ids = '8, 9, 10, 11, 12, 13, 14, 15';

        $dataFactory = $this->Plugin()->getDataFactory();

        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $rawData = $catDbAdapter->read($ids, $columns);

        $this->assertEquals($rawData[2]['name'], 'Beispiele');
        $this->assertEquals(count($rawData), 8);
    }

    public function testReadRecordIds()
    {
        $start = 0;
        $limit = 30;

        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $ids = $catDbAdapter->readRecordIds($start, $limit);
        $this->assertEquals(count($ids), 30);

        $allIds = $catDbAdapter->readRecordIds();
        $this->assertEquals(count($allIds), 62);
    }

    public function testDefaultColumns()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter('categories');

        $columns = $catDbAdapter->getDefaultColumns();

        $this->assertTrue(is_string($columns));
    }

//    public function testTree()
//    {
//        
//        $tree = array(
//            'category' => array(
//                'name' => 'description',
//                'parentId' => 'parent'
//            )
//        );
//
//        $tree = array('Category' => array(
//                array(
//                    '_attributes' => array('Attribute1' => '14', 'Attribute2' => '0'),
//                    'Id' => '14',
//                    'Title' => array('_attributes' => array('Attribute3' => 'en'), '_value' => 'Name1'),
//                    'Description' => array(
//                        'Value' => array('_attributes' => array('Attribute4' => 'en'), '_value' => 'This is desc'),
//                    )
//                ),
//                array(
//                    '_attributes' => array('Attribute1' => '15', 'Attribute2' => '14'),
//                    'Id' => '15',
//                    'Title' => array('_attributes' => array('Attribute3' => 'en'), '_value' => 'Name2'),
//                    'Description' => array(
//                        'Value' => array('_attributes' => array('Attribute4' => 'en'), '_value' => 'This is desc2'),
//                    )
//                ),
//                array(
//                    '_attributes' => array('Attribute1' => '16', 'Attribute2' => '14'),
//                    'Id' => '16',
//                    'Title' => array('_attributes' => array('Attribute3' => 'en'), '_value' => 'Name3'),
//                    'Description' => array(
//                        'Value' => array('_attributes' => array('Attribute4' => 'en'), '_value' => 'This is desc3'),
//                    )
//                ),
//            ),
//        );
//
//        $convert = new \Shopware_Components_Convert_Xml();
//        echo $convert->encode($tree);
//        exit;
//    }

}
