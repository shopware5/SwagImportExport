<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Shopware\Components\SwagImportExport\DataWorkflow;

class ImportExportTest extends ImportExportTestHelper
{
    public $test = false;
    
    public function removeWhiteSpaces($string)
    {
        return preg_replace('/\s+/', '', $string);
    }
    
    public function testXMLExportCycle()
    {
        $expectedHeader = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                            <Root>
                                <Header>
                                    <HeaderChild></HeaderChild>
                                </Header>
                                <Categories>
                            ';

        $expectedBody = '<Category Attribute1="3">
                                <Id>5</Id>
                                <Description Attribute2="1">
                                    <Value>Sommerwelten1</Value>
                                </Description>
                                <Title>Sommerwelten1</Title>
                            </Category>
                            <Category Attribute1="3">
                                <Id>6</Id>
                                <Description Attribute2="1">
                                    <Value>Genusswelten1</Value>
                                </Description>
                                <Title>Genusswelten1</Title>
                            </Category>
                            <Category Attribute1="3">
                                <Id>8</Id>
                                <Description Attribute2="1">
                                    <Value>Wohnwelten1</Value>
                                </Description>
                                <Title>Wohnwelten1</Title>
                            </Category>';

        $expectedFooter = '</Categories></Root>';

        $rawData['categories'] = array(
            array(
                'id' => 5,
                'parentId' => 3,
                'name' => 'Sommerwelten1',
                'active' => 1,
            ),
            array(
                'id' => 6,
                'parentId' => 3,
                'name' => 'Genusswelten1',
                'active' => 1,
            ),
            array(
                'id' => 8,
                'parentId' => 3,
                'name' => 'Wohnwelten1',
                'active' => 1,
            ),
        );

        $profileData = array(
            array('exportConversion', 'TestExportConversion'),
            array('tree', '{"name":"Root","children":[{"name":"Header","children":[{"name":"HeaderChild","shopwareField":""}]},{"name":"Categories","children":[{"name":"Category","adapter":"categories","attributes":[{"name":"Attribute1","shopwareField":"parentId"}],"children":[{"name":"Id","shopwareField":"id"},{"name":"Description","shopwareField":"name","children":[{"name":"Value","shopwareField":"name"}],"attributes":[{"name":"Attribute2","shopwareField":"active"}]},{"name":"Title","shopwareField":"name"}]}]}],"id":"root"}')
        );

        $params = array('format' => 'xml');

        $dataFactory = $this->Plugin()->getDataFactory();

        //Mocks db adapter
        $dbAdapter = $this->getMock('Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter');
        $dbAdapter->expects($this->any())->method('readRecordIds')->will($this->returnValue(array(3, 5, 7)));
        $dbAdapter->expects($this->any())->method('read')->will($this->returnValue($rawData));

        //Mocks data session entity
        $dataSession = $this->getMock('Shopware\Components\SwagImportExport\Session\Session');
        $dataSession->expects($this->at(1))->method('getState')->will($this->returnValue('new'));
        $dataSession->expects($this->at(4))->method('getState')->will($this->returnValue('active'));
        $dataSession->expects($this->at(7))->method('getState')->will($this->returnValue('finished'));
        $dataSession->expects($this->any())->method('getFormat')->will($this->returnValue('xml'));
        $dataSession->expects($this->any())->method('getType')->will($this->returnValue('export'));
        $dataSession->expects($this->any())->method('start')->will($this->returnValue());

        //Mocks profile
        $profile = $this->getMock('Shopware\Components\SwagImportExport\Profile\Profile');
        $profile->expects($this->any())->method('getType')->will($this->returnValue('categories'));
        $profile->expects($this->any())->method('getName')->will($this->returnValue('shopware categories'));
        $profile->expects($this->any())->method('getConfigNames')->will($this->returnValue(array('exportConversion', 'tree')));
        $profile->expects($this->any())
                ->method('getConfig')
                ->will($this->returnValueMap($profileData));

        //Mocks the file helper
        $fileHelper = $this->getMock('Shopware\Components\SwagImportExport\Utils\FileHelper');
        
        //Testing the header content of the XML
        $fileHelper->expects($this->at(0))
                ->method('writeStringToFile')
                ->will($this->returnCallback(function ($file, $actualXML) use ($expectedHeader) {
                            $actualXML = $this->removeWhiteSpaces($actualXML);
                            $expectedHeader = $this->removeWhiteSpaces($expectedHeader);
                            $this->assertEquals($expectedHeader, $actualXML);
                        }));

        //Testing the body content of the XML
        $fileHelper->expects($this->at(1))
                ->method('writeStringToFile')
                ->will($this->returnCallback(function ($file, $actualXML) use ($expectedBody) {
                            $actualXML = $this->removeWhiteSpaces($actualXML);
                            $expectedBody = $this->removeWhiteSpaces($expectedBody);
                            $this->assertEquals($expectedBody, $actualXML);
                        }));
                        
        //Testing the footer content of the XML
        $fileHelper->expects($this->at(2))
                ->method('writeStringToFile')
                ->will($this->returnCallback(function ($file, $actualXML) use ($expectedFooter) {
                            $actualXML = $this->removeWhiteSpaces($actualXML);
                            $expectedFooter = $this->removeWhiteSpaces($expectedFooter);
                            $this->assertEquals($expectedFooter, $actualXML);
                        }));

        $fileFacory = $this->Plugin()->getFileIOFactory();
        $fileWriter = $fileFacory->createFileWriter($params['format']);
        
        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        $colOpts = $dataFactory->createColOpts(null);
        $limit = $dataFactory->createLimit(null);
        $filter = $dataFactory->createFilter(null);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);
        $dataIO->initialize($colOpts, $limit, $filter, 'export', 'xml', $maxRecordCount);

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileWriter);

        $postData = array();

        $dataWorkflow->export($postData);
    }
    
    public function testXMLImportCycle()
    {
        $expectedRecords = array(
            'default' =>array(
                array(
                    'id' => '3',
                    'parentId' => '1',
                    'name' => 'Deutsch',
                    'metaKeywords' => 'Deutsch keywords',
                    'metaDescription' => 'Deutsch metadescription',
                    'active' => '1',
                ),
                array(
                    'id' => '4',
                    'parentId' => '1',
                    'name' => 'English',
                    'metaKeywords' => 'English keywords',
                    'metaDescription' => 'English metadescription',
                    'active' => '1',
                ),
                array(
                    'id' => '5',
                    'parentId' => '3',
                    'name' => 'Beispiele',
                    'metaKeywords' => 'Beispiele keywords',
                    'metaDescription' => 'Beispiele metadescription',
                    'active' => '0',
                ),
                array(
                    'id' => '6',
                    'parentId' => '4',
                    'name' => 'News',
                    'metaKeywords' => 'News keywords',
                    'metaDescription' => 'News metadescription',
                    'active' => '0',
                )
            )
        );
        
        $readData = array(
            array(
                'categoryID' => '3',
                'parentID' => '1',
                'description' => array(
                    '_value' => 'Deutsch',
                    '_attributes' => array(
                        'metakeywords' => 'Deutsch keywords'
                    )
                ),
                'metadescription' => 'Deutsch metadescription',
                'active' => '1',
            ),
            array(
                'categoryID' => '4',
                'parentID' => '1',
                'description' => array(
                    '_value' => 'English',
                    '_attributes' => array(
                        'metakeywords' => 'English keywords'
                    )
                ),
                'metadescription' => 'English metadescription',
                'active' => '1',
            ),
            array(
                'categoryID' => '5',
                'parentID' => '3',
                'description' => array(
                    '_value' => 'Beispiele',
                    '_attributes' => array(
                        'metakeywords' => 'Beispiele keywords'
                    )
                ),
                'metadescription' => 'Beispiele metadescription',
                'active' => '0',
            ),
            array(
                'categoryID' => '6',
                'parentID' => '4',
                'description' => array(
                    '_value' => 'News',
                    '_attributes' => array(
                        'metakeywords' => 'News keywords'
                    )
                ),
                'metadescription' => 'News metadescription',
                'active' => '0',
            ),
        );
        
        $profileData = array(
            array('exportConversion', 'TestExportConversion'),
            array('tree', '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"category","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53e9f539a997d","type":"leaf","index":0,"name":"categoryID","shopwareField":"id"},{"id":"53e0a853f1b98","type":"leaf","index":1,"name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":2,"name":"description","shopwareField":"name","attributes":[{"id":"542bc27379a9e","type":"attribute","index":0,"name":"metakeywords","shopwareField":"metaKeywords"}]},{"id":"53e0d17da1f06","type":"leaf","index":3,"name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5f87c87a","type":"leaf","index":4,"name":"active","shopwareField":"active"}]}]}]}')
        );

        $params = array(
            'format' => 'xml',
            'importFile' => 'test.xml'
        );

        $dataFactory = $this->Plugin()->getDataFactory();

        //Mocks db adapter
        $dbAdapter = $this->getMock('Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter');
        $dbAdapter->expects(
                $this->any())
                ->method('write')
                ->will($this->returnCallback(function ($records) use ($expectedRecords) {
                        $this->assertEquals($expectedRecords, $records);
                }));
        
        //Mocks profile
        $profile = $this->getMock('Shopware\Components\SwagImportExport\Profile\Profile');
        $profile->expects($this->any())->method('getType')->will($this->returnValue('categories'));
        $profile->expects($this->any())->method('getName')->will($this->returnValue('shopware categories'));
        $profile->expects($this->any())->method('getConfigNames')->will($this->returnValue(array('exportConversion', 'tree')));
        $profile->expects($this->any())
                ->method('getConfig')
                ->will($this->returnValueMap($profileData));
        
        //Mocks data session entity
        $dataSession = $this->getMock('Shopware\Components\SwagImportExport\Session\Session');
        $dataSession->expects($this->at(0))->method('getState')->will($this->returnValue('new'));
        $dataSession->expects($this->at(3))->method('getState')->will($this->returnValue('active'));
        $dataSession->expects($this->at(6))->method('getState')->will($this->returnValue('finished'));
        $dataSession->expects($this->any())->method('getFormat')->will($this->returnValue('xml'));
        $dataSession->expects($this->any())->method('getType')->will($this->returnValue('import'));
        $dataSession->expects($this->any())->method('start')->will($this->returnValue());
        
        
        // mock fileReader
        $fileReader = $this->getMock('Shopware\Components\SwagImportExport\FileIO\XmlFileReader');
        $fileReader->expects($this->any())->method('hasTreeStructure')->will($this->returnValue(true));
        $fileReader->expects($this->any())->method('readRecords')->will($this->returnValue($readData));
        
        
        
        //create dataIO
        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession);
        
        $dataIO->initialize($colOpts, $limit, $filter, 'import', 'xml', $maxRecordCount);

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileReader->hasTreeStructure())
        );

        $dataWorkflow = new DataWorkflow($dataIO, $profile, $dataTransformerChain, $fileReader);
        
        $dataWorkflow->import($params, $inputFile);
    }
}
