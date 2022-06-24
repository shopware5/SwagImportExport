<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

use SwagImportExport\Components\UploadPathProvider;

class CsvFileReader implements FileReader
{
    private const FORMAT = 'csv';

    protected bool $treeStructure = false;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(UploadPathProvider $uploadPathProvider)
    {
        $this->uploadPathProvider = $uploadPathProvider;
    }

    public function supports(string $format): bool
    {
        return $format === self::FORMAT;
    }

    /**
     * Reads csv records
     *
     * @throws \Exception
     *
     * @return array<mixed>
     */
    public function readRecords(string $fileName, int $position, int $step): array
    {
        // Make sure to detect CR LF (Windows) line breaks
        \ini_set('auto_detect_line_endings', '1');

        $tempFileName = '';
        if (\file_exists($fileName)) {
            $tempFileName = $this->uploadPathProvider->getRealPath(\md5(\microtime() . '.csv'));
            \file_put_contents($tempFileName, \file_get_contents($fileName));
            $fileName = $tempFileName;
        }

        $file = new \SplFileObject($fileName);
        $file->setCsvControl(';', '"');
        $file->setFlags(\SplFileObject::READ_CSV);

        $columnNames = $this->getColumnNames($file);

        // moves the file pointer to a certain line
        // +1 to ignore the first line of the file
        $file->seek($position + 1);

        $readRows = [];
        $data = [];
        for ($i = 1; $i <= $step; ++$i) {
            $row = $file->current();

            if ($row === false) {
                break;
            }

            if (\is_string($row)) {
                $row = [$row];
            }

            if ($this->isInvalidRecord($row)) {
                break;
            }

            foreach ($columnNames as $key => $name) {
                $data[$name] = isset($row[$key]) ? $row[$key] : '';
            }

            $readRows[] = $data;

            // Move the pointer to the next line
            $file->next();
        }

        \unlink($tempFileName);

        return $this->toUtf8($readRows);
    }

    public function hasTreeStructure(): bool
    {
        return $this->treeStructure;
    }

    /**
     * @param array<mixed> $tree
     */
    public function setTree(array $tree): void
    {
    }

    /**
     * Counts total rows of the entire CSV file
     *
     * @throws \Exception
     */
    public function getTotalCount(string $fileName): int
    {
        $fileHandler = \fopen($fileName, 'rb');

        if ($fileHandler === false) {
            throw new \Exception("Can not open file $fileName");
        }
        $counter = 0;

        while ($row = \fgetcsv($fileHandler, 0, ';')) {
            if ($this->isInvalidRecord($row)) {
                continue;
            }
            ++$counter;
        }

        \fclose($fileHandler);

        // removing first row /column names/
        return $counter - 1;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function toUtf8(array $rows): array
    {
        // detect whether the input is UTF-8 or ISO-8859-1
        \array_walk_recursive(
            $rows,
            function (&$value) {
                // will fail, if special chars are encoded to latin-1
                // $isUtf8 = (utf8_encode(utf8_decode($value)) == $value);

                // might have issues with encodings other than utf-8 and latin-1
                $isUtf8 = \mb_detect_encoding($value, 'UTF-8', true) !== false;
                if (!$isUtf8) {
                    $value = \utf8_encode($value);
                }

                return $value;
            }
        );

        return $rows;
    }

    /**
     * Returns column names of the given CSV file
     *
     * @return array<string>
     */
    private function getColumnNames(\SplFileObject $file): array
    {
        // Rewinds to first line
        $file->rewind();
        $columnNames = $file->current();

        // removes UTF-8 BOM
        foreach ($columnNames as $index => $name) {
            $columnNames[$index] = \str_replace("\xEF\xBB\xBF", '', $name);
        }

        return $columnNames;
    }

    /**
     * @param array<mixed> $row
     */
    private function isInvalidRecord(array $row): bool
    {
        return $row[0] === null;
    }
}
