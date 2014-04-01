<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\Components\SwagImportExport\Files\CsvFileWriter;
use Shopware\Components\SwagImportExport\Files\XmlFileWriter;
use Shopware\Components\SwagImportExport\Files\ExcelFileWriter;

class DataFactoryTest extends ImportExportTestHelper
{

    public function getPostData()
    {
        $postData = array(
            'columnOptions' => 'id, parent, description, active',
            'filter' => '',
            'limit' => array('limit' => 50, 'offset' => 150)
        );

        return $postData;
    }

    public function testFactories()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $this->assertTrue($dataFactory instanceof DataFactory, 'Is not a instance of DataFactory');

        $fileIOFactory = $this->Plugin()->getFileIOFactory();
        $this->assertTrue($fileIOFactory instanceof FileIOFactory, 'Is not a instance of DataFactory');
    }

    public function testDbAdapters()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        //tests categories data adapter
        $catergoriesDbAdapter = $dataFactory->createDbAdapter('categories');
        $this->assertTrue($catergoriesDbAdapter instanceof CategoriesDbAdapter, 'Is not a instance of CategoriesDbAdapter');

        //tests articles data adapter
        $articlesDbAdapter = $dataFactory->createDbAdapter('articles');
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

    public function testFiles()
    {
        $fileIOFactory = $this->Plugin()->getFileIOFactory();

        $csvFileWriter = $fileIOFactory->createFileWriter('csv');
        $this->assertTrue($csvFileWriter instanceof CsvFileWriter, 'Is not a instance of XmlFileWriter');

        $xmlFileWriter = $fileIOFactory->createFileWriter('xml');
        $this->assertTrue($xmlFileWriter instanceof XmlFileWriter, 'Is not a instance of XmlFileWriter');

        $excelFileWriter = $fileIOFactory->createFileWriter('excel');
        $this->assertTrue($excelFileWriter instanceof ExcelFileWriter, 'Is not a instance of XmlFileWriter');
    }

}
