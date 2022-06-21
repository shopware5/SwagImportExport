<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Components\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Components\Factories\DataFactory;
use SwagImportExport\Components\Factories\FileIOFactory;
use SwagImportExport\Components\FileIO\CsvFileReader;
use SwagImportExport\Components\FileIO\CsvFileWriter;
use SwagImportExport\Components\FileIO\XmlFileReader;
use SwagImportExport\Components\FileIO\XmlFileWriter;
use SwagImportExport\Components\Utils\DataColumnOptions;
use SwagImportExport\Components\Utils\DataFilter;
use SwagImportExport\Components\Utils\DataLimit;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ImportExportTestHelper;

class FactoryTest extends ImportExportTestHelper
{
    use ContainerTrait;

    /**
     * @return array<string, mixed>
     */
    public function getPostData(): array
    {
        return [
            'columnOptions' => 'id, parent, description, active',
            'filter' => [],
            'limit' => ['limit' => 50, 'offset' => 150],
            'max_record_count' => 100,
        ];
    }

    public function testFactories(): void
    {
        $dataFactory = $this->getContainer()->get(DataFactory::class);
        static::assertInstanceOf(DataFactory::class, $dataFactory, 'Is not a instance of DataFactory');

        $fileIOFactory = $this->getContainer()->get(FileIOFactory::class);
        static::assertInstanceOf(FileIOFactory::class, $fileIOFactory, 'Is not a instance of DataFactory');
    }

    public function testDbAdapters(): void
    {
        $dataFactory = $this->getContainer()->get(DataFactory::class);

        // tests categories data adapter
        $catergoriesDbAdapter = $dataFactory->createDbAdapter('categories');
        static::assertInstanceOf(CategoriesDbAdapter::class, $catergoriesDbAdapter, 'Is not a instance of CategoriesDbAdapter');

        // tests articles data adapter
        $articlesDbAdapter = $dataFactory->createDbAdapter('articles');
        static::assertInstanceOf(ArticlesDbAdapter::class, $articlesDbAdapter, 'Is not a instance of ArticlesDbAdapter');
    }

    public function testUtils(): void
    {
        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $postData = $this->getPostData();

        $colOpts = $dataFactory->createColOpts($postData['columnOptions']);
        static::assertInstanceOf(DataColumnOptions::class, $colOpts, 'Is not a instance of DataColumnOptions');

        $limit = $dataFactory->createLimit($postData['limit']);
        static::assertInstanceOf(DataLimit::class, $limit, 'Is not a instance of DataLimit');

        $filter = $dataFactory->createFilter($postData['filter']);
        static::assertInstanceOf(DataFilter::class, $filter, 'Is not a instance of DataFilter');
    }

    public function testFileIO(): void
    {
        $fileIOFactory = $this->getContainer()->get(FileIOFactory::class);

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
