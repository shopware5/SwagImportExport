<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\UploadPathProvider;

class CsvFileReaderTest extends \PHPUnit_Framework_TestCase
{
    private function createCsvFileReader()
    {
        $uploadPathProvider = new UploadPathProvider(Shopware()->DocPath());
        return new CsvFileReader($uploadPathProvider);
    }

    public function test_csv_file_without_empty_line_at_end_of_file()
    {
        $expectedResult = [
            [
                'testHeader' => 'testValue',
                'anotherTestHeader' => 'anotherTestValue'
            ]
        ];

        $csvFileReader = $this->createCsvFileReader();
        $actualRows = $csvFileReader->readRecords(__DIR__ . '/_fixtures/without_empty_line_on_end.csv', 0, 50);

        $this->assertEquals($expectedResult, $actualRows);
    }
}
