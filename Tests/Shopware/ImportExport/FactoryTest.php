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
use SwagImportExport\Tests\Helper\ImportExportTestHelper;

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
        static::assertInstanceOf(DataFactory::class, $dataFactory, 'Is not a instance of DataFactory');

        $fileIOFactory = $this->Plugin()->getFileIOFactory();
        static::assertInstanceOf(FileIOFactory::class, $fileIOFactory, 'Is not a instance of DataFactory');
    }

    public function testDbAdapters()
    {
        $dataFactory = $this->Plugin()->getDataFactory();

        // tests categories data adapter
        $catergoriesDbAdapter = $dataFactory->createDbAdapter('categories');
        static::assertInstanceOf(CategoriesDbAdapter::class, $catergoriesDbAdapter, 'Is not a instance of CategoriesDbAdapter');

        // tests articles data adapter
        $articlesDbAdapter = $dataFactory->createDbAdapter('articles');
        static::assertInstanceOf(ArticlesDbAdapter::class, $articlesDbAdapter, 'Is not a instance of ArticlesDbAdapter');
    }

    public function testUtils()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $postData = $this->getPostData();

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        static::assertInstanceOf(DataColumnOptions::class, $colOpts, 'Is not a instance of DataColumnOptions');

        $limit = $dataFactory->createLimit($postData['limit']);
        static::assertInstanceOf(DataLimit::class, $limit, 'Is not a instance of DataLimit');

        $filter = $dataFactory->createFilter($postData['filter']);
        static::assertInstanceOf(DataFilter::class, $filter, 'Is not a instance of DataFilter');
    }

    public function testFileIO()
    {
        $fileIOFactory = $this->Plugin()->getFileIOFactory();

        $csvFileWriter = $fileIOFactory->createFileWriter('csv');
        static::assertInstanceOf(CsvFileWriter::class, $csvFileWriter, 'Is not a instance of CsvFileWriter');

        $xmlFileWriter = $fileIOFactory->createFileWriter('xml');
        static::assertInstanceOf(XmlFileWriter::class, $xmlFileWriter, 'Is not a instance of XmlFileWriter');

        $csvFileReader = $fileIOFactory->createFileReader('csv');
        static::assertInstanceOf(CsvFileReader::class, $csvFileReader, 'Is not a instance of CsvFileReader');

        $xmlFileReader = $fileIOFactory->createFileReader('xml');
        static::assertInstanceOf(XmlFileReader::class, $xmlFileReader, 'Is not a instance of XmlFileReader');
    }
}
