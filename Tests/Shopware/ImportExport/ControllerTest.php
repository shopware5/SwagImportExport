<?php

namespace Tests\Shopware\ImportExport;

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
            $dataIO->startSession($profile->getEntity());
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

    public function testAPI()
    {
        //        $query = Shopware()->Models()->createQuery(
//                'SELECT c.id as id, c.active as active, c.name as name, c.position as position, c.parentId as parentId, (SELECT COUNT(c2.id)
//                FROM Shopware\Models\Category\Category c2
//                WHERE c2.parentId = c.id) as childrenCount, COUNT(articles) as articleCount
//                FROM Shopware\Models\Category\Category c LEFT JOIN c.allArticles articles
//                WHERE c.id IN :id GROUP BY c.id ORDER BY c.parentId ASC, c.position ASC'
//        );
//        $query->setParameter('id', '(3,5)');
//        $categories = $query->getArrayResult();
//
//        echo '<pre>';
//        var_dump($categories);
//        echo '</pre>';
//        exit;

        //categories
        $limit = $this->Request()->getParam('limit', 10);
        $offset = $this->Request()->getParam('start', 0);
        $sort = $this->Request()->getParam('sort', array());

        $start = microtime(true);
        $resource = \Shopware\Components\Api\Manager::getResource('category');

        $filter = array(
            array(
                'property' => 'id',
                'expression' => 'IN',
                'value' => array(3,5,6)
            )
        );

        $result = $resource->getList($offset, $limit, $filter, $sort);
        echo '<pre>';
        var_dump($result);
        echo '</pre>';
        exit;
        $time = microtime(true) - $start;
        echo '<pre>';
        var_dump($time);
        echo '</pre>';
        exit;

//        //products
//        $limit = $this->Request()->getParam('limit', 10);
//        $offset = $this->Request()->getParam('start', 0);
//        $filter = $this->Request()->getParam('filter', array());
//        $sort = $this->Request()->getParam('sort', array());
//
//        $resource = \Shopware\Components\Api\Manager::getResource('article');
//
//        $records = $resource->getList($offset, $limit, $filter, $sort);
//
//        echo '<pre>';
//        \Doctrine\Common\Util\Debug::dump($records['data'][0]);
//        echo '</pre>';
//        exit;
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

    public function testImportFakeArticles()
    {
        $builder = Shopware()->Models()->createQueryBuilder();
        
        $builder->select(array(
            'article.id as articleId',
            'variants.id as variantId',
            'article.name as name',
            'variants.additionalText as additionalText',
            'supplier.name as supplierName',
            'articleTax.tax as tax',
            'prices.price as netPrice',
            'prices.pseudoPrice as pseudoPrice',
            'prices.basePrice as basePrice',
            'article.active as active',
            'variants.inStock as inStock',
            'variants.stockMin as stockMin',
            'article.description as description',
            'article.descriptionLong as descriptionLong',
            'variants.shippingTime as shippingTime',
            'variants.shippingFree as shippingFree',
            'article.highlight as highlight',
            'article.metaTitle as metaTitle',
            'article.keywords as keywords',
            'variants.minPurchase as minPurchase',
            'variants.purchaseSteps as purchaseSteps',
            'variants.maxPurchase as maxPurchase',
            'variants.purchaseUnit as purchaseUnit',
            'variants.referenceUnit as referenceUnit',
            'variants.packUnit as packUnit',
            'variants.unitId as unitId',
            'article.priceGroupId as pricegroupId',
            'article.priceGroupActive as priceGroupActive',
            'article.lastStock as lastStock',
            'variants.supplierNumber as supplierNumber',
            'articleEsd.file as esd',
            'variants.weight as weight',
            'variants.width as width',
            'variants.height as height',
            'variants.len as length',
            'variants.ean as ean',
            'variantsUnit.unit as unit',
        ));

        $builder->from('Shopware\Models\Article\Detail', 'variants')
            ->leftJoin('variants.article', 'article')
            ->leftJoin('article.supplier', 'supplier')
            ->leftJoin('article.esds', 'articleEsd')
            ->leftJoin('variants.prices', 'prices')
            ->leftJoin('variants.unit', 'variantsUnit')
            ->leftJoin('article.tax', 'articleTax');
        $builder
                ->setFirstResult(100)
                ->setMaxResults(5);
        
        $startA = microtime(true);
        
        $query = $builder->getQuery();
        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $paginator = Shopware()->Models()->createPaginator($query);

        //returns the total count of the query
        $totalResult = $paginator->count();

        //returns the category data
        $articles = $paginator->getIterator()->getArrayCopy();
        
        echo microtime(true) - $startA;
        echo '<pre>';
        \Doctrine\Common\Util\Debug::dump(($articles));
        echo '</pre>';
        exit;
        

//        $articlesDetailRepo = Shopware()->Models()->getRepository('Shopware\Models\Article\Detail');

//        $articles = \Shopware\Components\Api\Manager::getResource('article');
//
//        $startA = microtime(true);
//        $result = $articles->getList(null, null);
//
//        echo microtime(true) - $startA;
//
//        echo '<pre>';
//        var_dump($result['total']);
//        echo '</pre>';
//        exit;
//
//        echo '<pre>';
//        var_dump($result['data'][0]);
//        echo '</pre>';
//        exit;
//        $article = array(
//            'supplierId' => '1',
//            'mainDetailId' => '3',
//            'taxId' => '1',
//            'name' => 'fake-02',
//            'description' => 'test description',
//            'active' => '1',
//            'priceGroupActive' => false,
//            'lastStock' => false,
//            'notification' => false,
//            'autoNumber' => "90064",
//            'attribute' => array(
//                array(
//
//                )
//            ),
//            'mainPrices' => array(
//                'price' => 777,
//                'customerGroupKey' => 'EK',
//                'customerGroup' => 0
//            ),
//            'mainDetail' => array(
//                array(
//                    'id' => 0,
//                    'articleId' => 0,
//                    'number' => 'DNH90064',
//                    'active' => true,
//                    'attribute' => array()
//                ),
//            )
//        );
//        $minimalTestArticle = array(
//            'name' => 'Turnschuh',
//            'active' => true,
//            'tax' => 19,
//            'supplier' => 'Turnschuh Inc.',
//            'mainDetail' => array(
//                'number' => 'turn',
//                'prices' => array(
//                    array(
//                        'customerGroupKey' => 'EK',
//                        'price' => 999,
//                    ),
//                )
//            ),
//            'configuratorSet' => array(
//                'groups' => array(
//                    array(
//                        'name' => 'Größe',
//                        'options' => array(
//                            array('name' => 'S'),
//                            array('name' => 'M'),
//                            array('name' => 'L'),
//                            array('name' => 'XL'),
//                            array('name' => 'XXL'),
//                        )
//                    ),
//                    array(
//                        'name' => 'Farbe',
//                        'options' => array(
//                            array('name' => 'Weiß'),
//                            array('name' => 'Gelb'),
//                            array('name' => 'Blau'),
//                            array('name' => 'Schwarz'),
//                            array('name' => 'Rot'),
//                        )
//                    ),
//                )
//            ),
//            'taxId' => 1,
//            'variants' => array(
//                array(
//                    'isMain' => true,
//                    'number' => 'turn',
//                    'inStock' => 15,
//                    'additionaltext' => 'L / Schwarz',
//                    'configuratorOptions' => array(
//                        array('group' => 'Größe', 'option' => 'L'),
//                        array('group' => 'Farbe', 'option' => 'Schwarz'),
//                    ),
//                    'prices' => array(
//                        array(
//                            'customerGroupKey' => 'EK',
//                            'price' => 1999,
//                        ),
//                    )
//                ),
//                array(
//                    'isMain' => false,
//                    'number' => 'turn.1',
//                    'inStock' => 15,
//                    'additionnaltext' => 'S / Schwarz',
//                    'configuratorOptions' => array(
//                        array('group' => 'Größe', 'option' => 'S'),
//                        array('group' => 'Farbe', 'option' => 'Schwarz'),
//                    ),
//                    'prices' => array(
//                        array(
//                            'customerGroupKey' => 'EK',
//                            'price' => 999,
//                        ),
//                    )
//                ),
//                array(
//                    'isMain' => false,
//                    'number' => 'turn.2',
//                    'inStock' => 15,
//                    'additionnaltext' => 'S / Rot',
//                    'configuratorOptions' => array(
//                        array('group' => 'Größe', 'option' => 'S'),
//                        array('group' => 'Farbe', 'option' => 'Rot'),
//                    ),
//                    'prices' => array(
//                        array(
//                            'customerGroupKey' => 'EK',
//                            'price' => 999,
//                        ),
//                    )
//                ),
//                array(
//                    'isMain' => false,
//                    'number' => 'turn.3',
//                    'inStock' => 15,
//                    'additionnaltext' => 'XL / Rot',
//                    'configuratorOptions' => array(
//                        array('group' => 'Größe', 'option' => 'XL'),
//                        array('group' => 'Farbe', 'option' => 'Rot'),
//                    ),
//                    'prices' => array(
//                        array(
//                            'customerGroupKey' => 'EK',
//                            'price' => 999,
//                        ),
//                    )
//                )
//            )
//        );

//        $minimalTestArticle = array(
//            'name' => 'Fake-Article-02',
//            'active' => true,
//            'tax' => 19,
//            'supplier' => 'Turnschuh Inc.',
//            'mainDetail' => array(
//                'number' => 'FAKE00002',
//                'prices' => array(
//                    array(
//                        'customerGroupKey' => 'EK',
//                        'price' => 999,
//                    ),
//                )
//            )
//        );
//
//        $start = microtime(true);
//        for ($index = 0; $index < 10000; $index++) {
//            $minimalTestArticle = array(
//                'name' => 'Fake-Article-' . $index,
//                'active' => true,
//                'tax' => 19,
//                'supplier' => 'Turnschuh Inc.',
//                'mainDetail' => array(
//                    'number' => 'FAKE0000' . $index,
//                    'prices' => array(
//                        array(
//                            'customerGroupKey' => 'EK',
//                            'price' => 999,
//                        ),
//                    )
//                )
//            );
//            $articles->create($minimalTestArticle);
//        }
//
//        $end = microtime(true);
//        //10000 articles for 1 min
//
//        echo $end - $start;
    }

    public function testImportXmlLifeCycle()
    {
        $postData = array(
            'profileId' => 1,
            'sessionId' => 72,
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
                echo $position, "\n";

                $records = $fileReader->readRecords($inputFileName, $position, 10);
                $data = $dataTransformerChain->transformBackward($records);
                echo "aaaa";
//                $data = $dataIO->read(100);
                // writing is successful, so we write the new position in the session;
                // if if the new position goes above the limits provided by the
                $dataIO->progressSession(10);
            } catch (\Exception $e) {
                echo $e->getMessage(), "\n";
                // we need to analyze the exception somehow and decide whether to break the while loop;
                // there is a danger of endless looping in case of some read error or transformation error;
                // may be we use
            }
        }

        exit;
    }
}
