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

    public function testHeader()
    {
        $jsonTree = $this->getJsonTree();

        $testData = 'Category_Attribute1;Category_Attribute2;Category.Id;Category.Title_Attribute3;Category.Title;Category.Description.Value_Attribute4;Category.Description.Value';

        $flattenTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('flatten', $jsonTree);

        $data = $flattenTransformer->composeHeader();
        
        $data = trim(preg_replace('/\s+/', '', $data));

        $this->assertEquals($testData, $data);
    }
    
    public function testData()
    {
        $jsonTree = $this->getJsonTree();

        $xmlData = '<Category Attribute1="3" Attribute2="1">
                        <Id>3</Id>
                        <Title Attribute3="1">Deutsch</Title>
                        <Description>
                            <Value Attribute4="1">Deutsch</Value>
                        </Description>
                    </Category>
                    <Category Attribute1="39" Attribute2="1">
                        <Id>39</Id>
                        <Title Attribute3="1">English</Title>
                        <Description>
                            <Value Attribute4="1">English</Value>
                        </Description>
                    </Category>';

        $testData = '3;1;3;1;Deutsch;1;Deutsch' . '39;1;39;1;English;1;English';
        
        $flattenTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('flatten', $jsonTree);

        $data = $flattenTransformer->transformForward($xmlData);
        
        $data = trim(preg_replace('/\s+/', '', $data));
        
        $this->assertEquals($testData, $data);
    }
    
}
