<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class DataTreeTransformerTest extends ImportExportTestHelper
{

    public function getJsonTree()
    {
        $jsonTree = '{ 
                        "name": "Root", 
                        "children": [{ 
                            "name": "Header", 
                            "children": [{ 
                                "name": "HeaderChild" 
                            }] 
                        },{
                            "name": "Categories", 
                            "children": [{ 
                                "name": "Category",
                                "type": "record",
                                "attributes": [{ 
                                    "name": "Attribute1",
                                    "shopwareField": "id"
                                },{ 
                                    "name": "Attribute2",
                                    "shopwareField": "parentid"
                                }],
                                "children": [{ 
                                    "name": "Id",
                                    "shopwareField": "id"
                                },{ 
                                    "name": "Title",
                                    "shopwareField": "name",
                                    "attributes": [{ 
                                        "name": "Attribute3",
                                        "shopwareField": "lang"
                                    }]
                                },{
                                    "name": "Description",
                                    "children": [{ 
                                        "name": "Value",
                                        "shopwareField": "description",
                                        "attributes": [{ 
                                            "name": "Attribute4",
                                            "shopwareField": "lang"
                                        }]
                                    }]
                                }]
                            }]
                        }] 
                    }';

        return $jsonTree;
    }

    public function testExportHeader()
    {
        $jsonTree = $this->getJsonTree();

        $testData = array(
                            'Category_Attribute1',
                            'Category_Attribute2',
                            'Category.Id',
                            'Category.Title_Attribute3',
                            'Category.Title',
                            'Category.Description.Value_Attribute4',
                            'Category.Description.Value'
                    );

        $flattenTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('flatten', $jsonTree);

        $data = $flattenTransformer->composeHeader();

        $this->assertEquals($testData, $data);
    }
    
    public function testExportData()
    {
        $jsonTree = $this->getJsonTree();

        $dataProvider = array(
            'Category' => array(
                array(
                    '_attributes' => array('Attribute1' => 47,'Attribute2' => 43),
                    'Id' => 47,
                    'Title' => array('_attributes' => array('Attribute3' => 1), '_value' => 'Teas and Accessories'),
                    'Description' => array('Value' => array('_attributes' => array('Attribute4' => 1), '_value' => 'Teas and Accessories')),
                )
        ));
        
        $testData = array(array(47,43,47,1,'Teas and Accessories', 1, 'Teas and Accessories'));
        
        $flattenTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('flatten', $jsonTree);

        $data = $flattenTransformer->transformForward($dataProvider);
        
        $this->assertEquals($testData, $data);
    }
    
}
