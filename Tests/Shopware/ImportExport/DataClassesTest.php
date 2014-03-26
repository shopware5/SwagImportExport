<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;

class DataClassesTest extends ImportExportTestHelper
{

    public function getPostData()
    {
        $postData = array(
            'columnOptions' => 'id, parent, description, active,',
            'filter' => '',
            'limit' => array('limit' => 50, 'offset' => 150)
        );

        return $postData;
    }

    public function testDataFactory()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $this->assertTrue($dataFactory instanceof DataFactory, 'Is not a instance of DataFactory');
    }

    public function testDbAdapters()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        //tests categories data adapter
        $catergoriesDbAdapter = $dataFactory->createCategoriesDbAdapter();
        $this->assertTrue($catergoriesDbAdapter instanceof CategoriesDbAdapter, 'Is not a instance of CategoriesDbAdapter');

        //tests articles data adapter
        $articlesDbAdapter = $dataFactory->createArticlesDbAdapter();
        $this->assertTrue($articlesDbAdapter instanceof ArticlesDbAdapter, 'Is not a instance of ArticlesDbAdapter');
    }

    public function testDataIO()
    {
        $type = 'categories';
        $postData = $this->getPostData();

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataIO = $dataFactory->getAdapter($type, $postData);
        $this->assertTrue($dataIO instanceof DataIO, 'Is not a instance of ArticlesDbAdapter');
    }

    public function testUtils()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $postData = $this->getPostData();

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $this->assertTrue($colOpts instanceof DataColumnOptions, 'Is not a instance of DataColumnOptions');

        $limit = $dataFactory->createLimit($postData['limit']);
        $this->assertTrue($limit instanceof DataLimit, 'Is not a instance of DataLimit');
        
        $filter = $dataFactory->createFilter($postData['filter']);
        $this->assertTrue($filter instanceof DataFilter, 'Is not a instance of DataFilter');
    }

}
