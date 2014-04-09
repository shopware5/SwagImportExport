<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class ProfileTest extends ImportExportTestHelper
{
/*
    public function testCreateProfile()
    {
        $name = 'magento';
        $type = 'categories';
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


        $exportConversion = '{if $active} false {else} true {/if}';

        
        $postData['profileId'] = 1;
        
        
        $profileFactory = $this->Plugin()->getProfileFactory();
        $profile = $profileFactory->loadProfile($postData);
        
        $tree = $profile->getConfig('treeBuilder');
        
        
        
        echo '<pre>';
        var_dump(json_decode($tree, true));
        echo '</pre>';
        exit;
    }*/

}
