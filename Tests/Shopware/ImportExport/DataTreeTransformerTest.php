<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class DataFlatenTransformerTest extends ImportExportTestHelper
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

    public function testTreeTransformer()
    {
        $providedData = array(
            array(
                '_attributes' => array('Attribute1' => '14', 'Attribute2' => '0'),
                'Id' => '14',
                'Title' => array('_attributes' => array('Attribute3' => 'en'), '_value' => 'Name1'),
                'Description' => array(
                    'Value' => array('_attributes' => array('Attribute4' => 'en'), '_value' => 'This is desc')
                )
            ),
            array(
                '_attributes' => array('Attribute1' => '15', 'Attribute2' => '14'),
                'Id' => '15',
                'Title' => array('_attributes' => array('Attribute3' => 'en'), '_value' => 'Name2'),
                'Description' => array(
                    'Value' => array('_attributes' => array('Attribute4' => 'en'), '_value' => 'This is desc2')
                )
            ),
            array(
                '_attributes' => array('Attribute1' => '16', 'Attribute2' => '14'),
                'Id' => '16',
                'Title' => array('_attributes' => array('Attribute3' => 'en'), '_value' => 'Name3'),
                'Description' => array(
                    'Value' => array('_attributes' => array('Attribute4' => 'en'), '_value' => 'This is desc3')
                )
            ),
        );

        $convert = new \Shopware_Components_Convert_Xml();
        $testData = trim($convert->_encode(array('Category' => $providedData)));

        $jsonTree = $this->getJsonTree();

        $rawData = array(
            array('id' => 14, 'parentid' => 0, 'name' => 'Name1', 'description' => 'This is desc', 'lang' => 'en',),
            array('id' => 15, 'parentid' => 14, 'name' => 'Name2', 'description' => 'This is desc2', 'lang' => 'en',),
            array('id' => 16, 'parentid' => 14, 'name' => 'Name3', 'description' => 'This is desc3', 'lang' => 'en',),
        );

        $treeTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('tree', $jsonTree);

        $data = $treeTransformer->transformForward($rawData);

        $this->assertEquals($testData, $data);
    }

    public function testHeader()
    {
        $jsonTree = $this->getJsonTree();

        $testData = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                    <root>
                        <Header>
                            <HeaderChild></HeaderChild>
                        </Header>
                        <Categories>';

        $testData = trim(preg_replace('/\s+/', '', $testData));

        $treeTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('tree', $jsonTree);

        $data = $treeTransformer->composeHeader();

        $data = trim(preg_replace('/\s+/', '', $data));

        $this->assertEquals($testData, $data);
    }

    public function testFooter()
    {
        $jsonTree = $this->getJsonTree();

        $testData = '</Categories></root>';

        $treeTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('tree', $jsonTree);

        $data = $treeTransformer->composeFooter();

        $data = trim(preg_replace('/\s+/', '', $data));

        $this->assertEquals($testData, $data);
    }
}
