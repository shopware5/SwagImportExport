<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class ControllerTest extends ImportExportTestHelper
{

    public function testExportLifeCycle()
    {
//        $name = 'magento';
//        $type = 'categories';
//        $jsonTree = '{ 
//                        "name": "Root", 
//                        "children": [{ 
//                            "name": "Header", 
//                            "children": [{ 
//                                "name": "HeaderChild" 
//                            }] 
//                        },{
//                            "name": "Categories", 
//                            "children": [{ 
//                                "name": "Category",
//                                "type": "record",
//                                "attributes": [{ 
//                                    "name": "Attribute1",
//                                    "shopwareField": "id"
//                                },{ 
//                                    "name": "Attribute2",
//                                    "shopwareField": "parentid"
//                                }],
//                                "children": [{ 
//                                    "name": "Id",
//                                    "shopwareField": "id"
//                                },{ 
//                                    "name": "Title",
//                                    "shopwareField": "name",
//                                    "attributes": [{ 
//                                        "name": "Attribute3",
//                                        "shopwareField": "lang"
//                                    }]
//                                },{
//                                    "name": "Description",
//                                    "children": [{ 
//                                        "name": "Value",
//                                        "shopwareField": "description",
//                                        "attributes": [{ 
//                                            "name": "Attribute4",
//                                            "shopwareField": "lang"
//                                        }]
//                                    }]
//                                }]
//                            }]
//                        }] 
//                    }';
//
//
//        $exportConversion = '{if $active} false {else} true {/if}';
//
//        
        $postData = array(
            'profileId' => 1,
            'sessionId' => 70,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'csv',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataIO = $dataFactory->createDataIO($postData);

        // we create the file writer that will write (partially) the result file
        $fileWriter = $this->Plugin()->getFileIOFactory()->createFileWriter($postData);

//        $outputFileName = Shopware()->DocPath() . 'files/import_export/_test.xml';
//        $outputFileName = Shopware()->DocPath() . 'files/import_export/test.csv';

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        if ($dataIO->getSessionState() == 'new') {
            //todo: create file here ?
            $fileName = $dataIO->generateFileName($profile);
            $outputFileName = Shopware()->DocPath() . 'files/import_export/' . $fileName;
            
            // session has no ids stored yet, therefore we must start it and write the file headers
            $header = $dataTransformerChain->composeHeader();
            $fileWriter->writeHeader($outputFileName, $header);
            $dataIO->startSession();
        } else {
            //todo: create file here ?
            $fileName = $dataIO->generateFileName($profile);
           
            $outputFileName = Shopware()->DocPath() . 'files/import_export/' . $fileName;
            
            // session has already loaded ids and some position, so we simply activate it
            $dataIO->resumeSession();
        }

        $dataIO->preloadRecordIds();

        while ($dataIO->getSessionState() == 'active') {

            try {

                // read a bunch of records into simple php array;
                // the count of records may be less than 100 if we are at the end of the read.
                $data = $dataIO->read(100);

                // process that array with the full transformation chain
                $data = $dataTransformerChain->transformForward($data);
                
                // now the array should be a tree and we write it to the file
                $fileWriter->writeRecords($outputFileName, $data);

                // writing is successful, so we write the new position in the session;
                // if if the new position goes above the limits provided by the 
                $dataIO->progressSession(100);
            } catch (Exception $e) {
                // we need to analyze the exception somehow and decide whether to break the while loop;
                // there is a danger of endless looping in case of some read error or transformation error;
                // may be we use
            }
        }

        if ($dataIO->getSessionState() == 'finished') {
            // Session finished means we have exported all the ids in the sesssion.
            // Therefore we can close the file with a footer and mark the session as done.
            $footer = $dataTransformerChain->composeFooter();
            $fileWriter->writeFooter($outputFileName, $footer);
            $dataIO->closeSession();
        }

        echo '<pre>';
        var_dump('completed');
        echo '</pre>';
        exit;
    }

    public function testImportLifeCycle()
    {
        $postData = array(
            'profileId' => 1,
            'sessionId' => 12,
            'type' => 'import',
            'max_record_count' => 100,
            'format' => 'csv',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataIO = $this->Plugin()->getDataFactory()->createDataIO($postData);
        
        if ($dataIO->getSessionState() == 'closed') {
            echo '<pre>';
            var_dump('This session is already import.');
            echo '</pre>';
            exit;
        }
        
        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData);

        $inputFileName = Shopware()->DocPath() . 'files/import_export/test.csv';

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileReader->hasTreeStructure())
        );

        if ($dataIO->getSessionState() == 'new') {

            $totalCount = $fileReader->getTotalCount($inputFileName);
            
            $dataIO->getDataSession()->setFileName($inputFileName);

            $dataIO->getDataSession()->setTotalCount($totalCount);

            $dataIO->startSession();
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $dataIO->resumeSession();
        }

        while ($dataIO->getSessionState() == 'active') {

            try {

                //get current session position
                $position = $dataIO->getSessionPosition();

                $records = $fileReader->readRecords($inputFileName, $position, 100);

                $data = $dataTransformerChain->transformBackward($records);
                
                $dataIO->write($data);
                
                $dataIO->progressSession();
            } catch (Exception $e) {
                // we need to analyze the exception somehow and decide whether to break the while loop;
                // there is a danger of endless looping in case of some read error or transformation error;
                // may be we use
            }
        }
        
        if ($dataIO->getSessionState() == 'finished') {
            $dataIO->closeSession();
        }
    }
    
//    public function testImportFakeCategories()
//    {
//        $fakeCategories = '';
//        for ($index = 0; $index < 10000; $index++) {
//            $fakeCategories .=",(3,'FakeCategory-$index',1)";
//        }
//        $fakeCategories[0] = ' ';
//                
//        $query = "REPLACE INTO `s_categories` (`parent`,`description`,`active`) VALUES $fakeCategories";
//        
//        Shopware()->Db()->query($query);
//        
//        echo 'Fake categories were added';
//        exit;
//    }

    public function testImportXmlLifeCycle()
    {
        $postData = array(
            'profileId' => 1,
            'sessionId' => 18,
            'type' => 'import',
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataIO = $this->Plugin()->getDataFactory()->createDataIO($postData);

        // we create the file reader that will read the result file
        $fileReader = $this->Plugin()->getFileIOFactory()->createFileReader($postData);

        $inputFileName = Shopware()->DocPath() . 'files/import_export/test.xml';

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileReader->hasTreeStructure())
        );

        $tree = json_decode($profile->getConfig("tree"), true);
        $fileReader->setTree($tree);

        if ($dataIO->getSessionState() == 'new') {

            $totalCount = $fileReader->getTotalCount($inputFileName);

            $dataIO->getDataSession()->setTotalCount($totalCount);

            $dataIO->startSession();
        } else {
            // session has already loaded ids and some position, so we simply activate it
            $dataIO->resumeSession();
        }


        while ($dataIO->getSessionState() == 'active') {
            try {
                $position = $dataIO->getSessionPosition();

                $records = $fileReader->readRecords($inputFileName, $position, 100);
                $data = $dataTransformerChain->transformBackward($records);
                
//                $data = $dataIO->read(100);
                // writing is successful, so we write the new position in the session;
                // if if the new position goes above the limits provided by the 
                $dataIO->progressSession();
            } catch (Exception $e) {
                // we need to analyze the exception somehow and decide whether to break the while loop;
                // there is a danger of endless looping in case of some read error or transformation error;
                // may be we use
            }
        }

        exit;
    }

}
