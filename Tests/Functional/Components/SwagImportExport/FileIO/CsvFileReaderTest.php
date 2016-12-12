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
    const AMOUNT_OF_RECORDS_WITHOUT_EMPTY_LINE = 1;
    const AMOUNT_OF_RECORDS_WITH_EMPTY_LINE = 1;

    private function createCsvFileReader()
    {
        $uploadPathProvider = new UploadPathProvider(Shopware()->DocPath());
        return new CsvFileReader($uploadPathProvider);
    }

    public function test_readRecords_with_csv_file_without_empty_line_at_end_of_file()
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

    public function test_getTotalCount_without_empty_line_at_the_end_of_file()
    {
        $csvFileReader = $this->createCsvFileReader();
        $countOfRecords = $csvFileReader->getTotalCount(__DIR__ . '/_fixtures/without_empty_line_on_end.csv');

        $this->assertEquals(self::AMOUNT_OF_RECORDS_WITHOUT_EMPTY_LINE, $countOfRecords);
    }

    public function test_getTotalCount_with_multiple_empty_lines_at_the_end_of_file()
    {
        $csvFileReader = $this->createCsvFileReader();
        $countOfRecords = $csvFileReader->getTotalCount(__DIR__ . '/_fixtures/empty_lines_on_end.csv');

        $this->assertEquals(self::AMOUNT_OF_RECORDS_WITH_EMPTY_LINE, $countOfRecords);
    }
}
