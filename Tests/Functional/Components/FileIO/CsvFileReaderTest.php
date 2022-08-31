<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\FileIO;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\FileIO\CsvFileReader;
use SwagImportExport\Components\UploadPathProvider;

class CsvFileReaderTest extends TestCase
{
    public const AMOUNT_OF_RECORDS_WITHOUT_EMPTY_LINE = 1;
    public const AMOUNT_OF_RECORDS_WITH_EMPTY_LINE = 1;

    public function testReadRecordsWithCsvFileWithoutEmptyLineAtEndOfFile(): void
    {
        $expectedResult = [
            [
                'testHeader' => 'testValue',
                'anotherTestHeader' => 'anotherTestValue',
            ],
        ];

        $actualRows = $this->createCsvFileReader()
            ->readRecords(__DIR__ . '/_fixtures/without_empty_line_on_end.csv', 0, 50);

        static::assertEquals($expectedResult, $actualRows);
    }

    public function testGetTotalCountWithoutEmptyLineAtTheEndOfFile(): void
    {
        $countOfRecords = $this->createCsvFileReader()->getTotalCount(__DIR__ . '/_fixtures/without_empty_line_on_end.csv');

        static::assertEquals(self::AMOUNT_OF_RECORDS_WITHOUT_EMPTY_LINE, $countOfRecords);
    }

    public function testGetTotalCountWithMultipleEmptyLinesAtTheEndOfFile(): void
    {
        $countOfRecords = $this->createCsvFileReader()->getTotalCount(__DIR__ . '/_fixtures/empty_lines_on_end.csv');

        static::assertEquals(self::AMOUNT_OF_RECORDS_WITH_EMPTY_LINE, $countOfRecords);
    }

    private function createCsvFileReader(): CsvFileReader
    {
        $uploadPathProvider = new UploadPathProvider(Shopware()->DocPath());

        return new CsvFileReader($uploadPathProvider);
    }
}
