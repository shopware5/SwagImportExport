<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class ControllerTest extends ImportExportTestHelper
{

    public function testCreateProfile()
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
        $postData['profileId'] = 1;
        $postData['type'] = 'xml';

        $postData = array(
            'profileId' => 1,
            'sessionId' => 1,
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'xml',
            'adapter' => 'categories',
        );

        $profile = $this->Plugin()->getProfileFactory()->loadProfile($postData);

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataIO = $dataFactory->createDataIO($postData);

        // we create the file writer that will write (partially) the result file
        $fileWriter = $this->Plugin()->getFileIOFactory()->createFileWriter($postData);

        $outputFileName = Shopware()->DocPath() . 'files/import_export/test.xml';

        $dataTransformerChain = $this->Plugin()->getDataTransformerFactory()->createDataTransformerChain(
                $profile, array('isTree' => $fileWriter->hasTreeStructure())
        );

        if ($dataIO->getSessionState() == 'new') {
            // session has no ids stored yet, therefore we must start it and write the file headers
            $header = $dataTransformerChain->composeHeader();
            $fileWriter->writeHeader($outputFileName, $header);
            $dataIO->startSession();
        } else {
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
                $dataIO->progressSession();
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

}
