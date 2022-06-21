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
    protected bool $treeStructure = false;

    protected FileHelper $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

    public function writeHeader(string $fileName, array $headerData): void
    {
        if (!\is_array($headerData)) {
            throw new \Exception('Header data is not valid');
        }
        $columnNames = \implode(';', $headerData) . "\n";
        $this->getFileHelper()->writeStringToFile($fileName, $columnNames);
    }

    public function writeRecords(string $fileName, array $data): void
    {
        $flatData = '';

        $convertor = new CsvConverter();
        $keys = \array_keys(\current($data));
        foreach ($data as $line) {
            $flatData .= $convertor->_encode_line($line, $keys) . $convertor->sSettings['newline'];
        }
        $this->getFileHelper()->writeStringToFile($fileName, $flatData, \FILE_APPEND);
    }

    public function writeFooter(string $fileName, ?array $footerData): void
    {
    }

    public function hasTreeStructure(): bool
    {
        return $this->treeStructure;
    }

    public function getFileHelper(): FileHelper
    {
        return $this->fileHelper;
    }
}
