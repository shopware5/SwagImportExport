<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\ExportControllerTrait;

class ExportControllerTraitTest extends TestCase
{
    use ExportControllerTrait;

    public function test_csvToArrayIndexedByFieldValue_should_return_mapped_array_and_indexed_by_identifier()
    {
        $filePath = __DIR__ . '/_fixtures/example.csv';
        $indexedCsvAsArray = $this->csvToArrayIndexedByFieldValue($filePath, 'identifier');

        $this->assertEquals('Ein Test', $indexedCsvAsArray[123]['name']);
    }

    public function test_csvToArrayIndexedByFieldValue_should_fill_missing_fields()
    {
        $filePath = __DIR__ . '/_fixtures/csv_with_missing_fields.csv';
        $indexedCsvAsArray = $this->csvToArrayIndexedByFieldValue($filePath, 'header02');

        $this->assertEquals('', $indexedCsvAsArray['value02']['field_without_value']);
    }

    public function test_csvToArrayIndexedByFieldValue_should_remove_header()
    {
        $expectedFirstArrayElement = [
            'name' => 'Ein Test',
            'identifier' => 123,
            'anotherHeader' => 'anotherValue',
        ];

        $filePath = __DIR__ . '/_fixtures/example.csv';
        $indexedCsvAsArray = $this->csvToArrayIndexedByFieldValue($filePath, 'identifier');

        $this->assertEquals($expectedFirstArrayElement, array_shift($indexedCsvAsArray));
    }
}
