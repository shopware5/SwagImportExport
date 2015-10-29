<?php

namespace Tests\Shopware\ImportExport;

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

    public function testTreeTransformer()
    {
        $providedData = array('Category' => array(
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
        ));

        $jsonTree = $this->getJsonTree();

        $rawData = array(
            array('id' => 14, 'parentid' => 0, 'name' => 'Name1', 'description' => 'This is desc', 'lang' => 'en',),
            array('id' => 15, 'parentid' => 14, 'name' => 'Name2', 'description' => 'This is desc2', 'lang' => 'en',),
            array('id' => 16, 'parentid' => 14, 'name' => 'Name3', 'description' => 'This is desc3', 'lang' => 'en',),
        );

        $treeTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('tree', $jsonTree);

        $data = $treeTransformer->transformForward($rawData);

        $this->assertEquals($providedData, $data);
    }

    public function testHeaderFooter()
    {
        $jsonTree = $this->getJsonTree();

        $testData = array('Root' => array(
            'Header' => array('HeaderChild' => null),
            'Categories' => array('_currentMarker' => null),
        ));

        $treeTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('tree', $jsonTree);

        $headerData = $treeTransformer->composeHeader();
        $footerData = $treeTransformer->composeFooter();
        
        $this->assertEquals($testData, $headerData);
        $this->assertEquals($testData, $footerData);
    }

//    public function testImportData()
//    {
//        $jsonTree = $this->getJsonTree();
//
//        $xmlData = '<root>
//                        <Header>
//                            <HeaderChild></HeaderChild>
//                        </Header>
//                        <Categories>
//                            <Category Attribute1="3" Attribute2="1">
//                                <Id>3</Id>
//                                <Title Attribute3="1">Deutsch</Title>
//                                <Description>
//                                    <Value Attribute4="1">Deutsch</Value>
//                                </Description>
//                            </Category>
//                            <Category Attribute1="39" Attribute2="1">
//                                <Id>39</Id>
//                                <Title Attribute3="1">English</Title>
//                                <Description>
//                                    <Value Attribute4="1">English</Value>
//                                </Description>
//                            </Category>
//                        </Categories>
//                    </root>';
//
//        $readedXml = '<Category Attribute1="3" Attribute2="1">
//                                <Id>3</Id>
//                                <Title Attribute3="1">Deutsch</Title>
//                                <Description>
//                                    <Value Attribute4="1">Deutsch</Value>
//                                </Description>
//                            </Category>
//                            <Category Attribute1="39" Attribute2="1">
//                                <Id>39</Id>
//                                <Title Attribute3="1">English</Title>
//                                <Description>
//                                    <Value Attribute4="1">English</Value>
//                                </Description>
//                            </Category>';
//
//        $treeTransformer = $this->Plugin()->getDataTransformerFactory()->createDataTransformer('tree', $jsonTree);
//
//        //todo: this should come from the reader
//        $inputFileName = Shopware()->DocPath() . 'files/import_export/test.xml';
//
//        $xml = new \XMLReader();
//        $xml->open($inputFileName);
//
//        $countElements = 0;
//
//        $mStart = microtime(true);
//
//        while ($xml->read()) {
//            if ($xml->nodeType == \XMLReader::END_ELEMENT && $xml->name == 'Category') {
//                $countElements++;
//            }
//        }
//
//        $mStop = microtime(true);
//
//
//        $dataArray = $convertData['root']['Categories']['Category'];
//
//        $data = $treeTransformer->transformBackward($dataArray);
//    }
}
