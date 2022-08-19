<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Components\DbAdapters\ProductsDbAdapter;
use SwagImportExport\Components\FileIO\CsvFileReader;
use SwagImportExport\Components\FileIO\CsvFileWriter;
use SwagImportExport\Components\FileIO\XmlFileReader;
use SwagImportExport\Components\FileIO\XmlFileWriter;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Components\Providers\FileIOProvider;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class FactoryTest extends TestCase
{
    use DatabaseTestCaseTrait;
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
        $dataProvider = $this->getContainer()->get(DataProvider::class);
        static::assertInstanceOf(DataProvider::class, $dataProvider, 'Is not an instance of DataProvider');

        $fileIOFactory = $this->getContainer()->get(FileIOProvider::class);
        static::assertInstanceOf(FileIOProvider::class, $fileIOFactory, 'Is not an instance of DataProvider');
    }

    public function testDbAdapters(): void
    {
        $dataProvider = $this->getContainer()->get(DataProvider::class);

        // tests categories data adapter
        $catergoriesDbAdapter = $dataProvider->createDbAdapter('categories');
        static::assertInstanceOf(CategoriesDbAdapter::class, $catergoriesDbAdapter, 'Is not an instance of CategoriesDbAdapter');

        // tests products data adapter
        $productsDbAdapter = $dataProvider->createDbAdapter('articles');
        static::assertInstanceOf(ProductsDbAdapter::class, $productsDbAdapter, 'Is not an instance of ProductsDbAdapter');
    }

    public function testFileIO(): void
    {
        $fileIOFactory = $this->getContainer()->get(FileIOProvider::class);

        $csvFileWriter = $fileIOFactory->getFileWriter('csv');
        static::assertInstanceOf(CsvFileWriter::class, $csvFileWriter, 'Is not an instance of CsvFileWriter');

        $xmlFileWriter = $fileIOFactory->getFileWriter('xml');
        static::assertInstanceOf(XmlFileWriter::class, $xmlFileWriter, 'Is not an instance of XmlFileWriter');

        $csvFileReader = $fileIOFactory->getFileReader('csv');
        static::assertInstanceOf(CsvFileReader::class, $csvFileReader, 'Is not an instance of CsvFileReader');

        $xmlFileReader = $fileIOFactory->getFileReader('xml');
        static::assertInstanceOf(XmlFileReader::class, $xmlFileReader, 'Is not an instance of XmlFileReader');
    }
}
