<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

use SwagImportExport\Components\Converter\CsvConverter;
use SwagImportExport\Components\Utils\FileHelper;

class CsvFileWriter implements FileWriter
{
    private const FORMAT = 'csv';

    protected bool $treeStructure = false;

    protected FileHelper $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

    public function supports(string $format): bool
    {
        return $format === self::FORMAT;
    }

    public function writeHeader(string $fileName, array $headerData): void
    {
        $columnNames = \implode(';', $headerData) . "\n";
        $this->fileHelper->writeStringToFile($fileName, $columnNames);
    }

    public function writeRecords(string $fileName, array $treeData): void
    {
        $flatData = '';

        $convertor = new CsvConverter();
        $keys = \array_keys(\current($treeData));
        foreach ($treeData as $line) {
            $flatData .= $convertor->encodeLine($line, $keys) . $convertor->getNewline();
        }
        $this->fileHelper->writeStringToFile($fileName, $flatData, \FILE_APPEND);
    }

    public function writeFooter(string $fileName, ?array $footerData): void
    {
    }

    public function hasTreeStructure(): bool
    {
        return $this->treeStructure;
    }
}
