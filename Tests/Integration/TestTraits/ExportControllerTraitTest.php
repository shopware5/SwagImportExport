<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\TestTraits;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ExportControllerTrait;

class ExportControllerTraitTest extends TestCase
{
    use ExportControllerTrait;
    use ContainerTrait;

    public function testCsvToArrayIndexedByFieldValueShouldReturnMappedArrayAndIndexedByIdentifier(): void
    {
        $filePath = __DIR__ . '/_fixtures/example.csv';
        $indexedCsvAsArray = $this->csvToArrayIndexedByFieldValue($filePath, 'identifier');

        static::assertEquals('Ein Test', $indexedCsvAsArray[123]['name']);
    }

    public function testCsvToArrayIndexedByFieldValueShouldFillMissingFields(): void
    {
        $filePath = __DIR__ . '/_fixtures/csv_with_missing_fields.csv';
        $indexedCsvAsArray = $this->csvToArrayIndexedByFieldValue($filePath, 'header02');

        static::assertEquals('', $indexedCsvAsArray['value02']['field_without_value']);
    }

    public function testCsvToArrayIndexedByFieldValueShouldRemoveHeader(): void
    {
        $expectedFirstArrayElement = [
            'name' => 'Ein Test',
            'identifier' => 123,
            'anotherHeader' => 'anotherValue',
        ];

        $filePath = __DIR__ . '/_fixtures/example.csv';
        $indexedCsvAsArray = $this->csvToArrayIndexedByFieldValue($filePath, 'identifier');

        static::assertEquals($expectedFirstArrayElement, \array_shift($indexedCsvAsArray));
    }
}
