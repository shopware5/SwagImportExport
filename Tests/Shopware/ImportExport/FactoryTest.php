<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Factories\FileIOFactory;
use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\FileIO\XmlFileReader;
use Shopware\Components\SwagImportExport\FileIO\XmlFileWriter;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Tests\Helper\ImportExportTestHelper;

class FactoryTest extends ImportExportTestHelper
{
    /**
     * @return array
     */
    public function getPostData()
    {
        $postData = [
            'columnOptions' => 'id, parent, description, active',
            'filter' => '',
            'limit' => ['limit' => 50, 'offset' => 150],
            'max_record_count' => 100,
        ];

        return $postData;
    }

    public function testFactories()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $this->assertInstanceOf(DataFactory::class, $dataFactory, 'Is not a instance of DataFactory');

        $fileIOFactory = $this->Plugin()->getFileIOFactory();
        $this->assertInstanceOf(FileIOFactory::class, $fileIOFactory, 'Is not a instance of DataFactory');
    }

    public function testDbAdapters()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        //tests categories data adapter
        $catergoriesDbAdapter = $dataFactory->createDbAdapter('categories');
        $this->assertInstanceOf(CategoriesDbAdapter::class, $catergoriesDbAdapter, 'Is not a instance of CategoriesDbAdapter');

        //tests articles data adapter
        $articlesDbAdapter = $dataFactory->createDbAdapter('articles');
        $this->assertInstanceOf(ArticlesDbAdapter::class, $articlesDbAdapter, 'Is not a instance of ArticlesDbAdapter');
    }

    public function testUtils()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $postData = $this->getPostData();

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        $this->assertInstanceOf(DataColumnOptions::class, $colOpts, 'Is not a instance of DataColumnOptions');

        $limit = $dataFactory->createLimit($postData['limit']);
        $this->assertInstanceOf(DataLimit::class, $limit, 'Is not a instance of DataLimit');

        $filter = $dataFactory->createFilter($postData['filter']);
        $this->assertInstanceOf(DataFilter::class, $filter, 'Is not a instance of DataFilter');
    }

    public function testFileIO()
    {
        $fileIOFactory = $this->Plugin()->getFileIOFactory();

        $csvFileWriter = $fileIOFactory->createFileWriter('csv');
        $this->assertInstanceOf(CsvFileWriter::class, $csvFileWriter, 'Is not a instance of CsvFileWriter');

        $xmlFileWriter = $fileIOFactory->createFileWriter('xml');
        $this->assertInstanceOf(XmlFileWriter::class, $xmlFileWriter, 'Is not a instance of XmlFileWriter');

        $csvFileReader = $fileIOFactory->createFileReader('csv');
        $this->assertInstanceOf(CsvFileReader::class, $csvFileReader, 'Is not a instance of CsvFileReader');

        $xmlFileReader = $fileIOFactory->createFileReader('xml');
        $this->assertInstanceOf(XmlFileReader::class, $xmlFileReader, 'Is not a instance of XmlFileReader');
    }
}
