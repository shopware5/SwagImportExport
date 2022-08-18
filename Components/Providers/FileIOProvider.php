<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Providers;

use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\FileIO\FileWriter;

class FileIOProvider implements \Enlight_Hook
{
    /**
     * @var iterable<FileWriter>
     */
    private iterable $fileWriter;

    /**
     * @var iterable<FileReader>
     */
    private iterable $fileReader;

    /**
     * @param iterable<FileWriter> $fileWriter
     * @param iterable<FileReader> $fileReader
     */
    public function __construct(
        iterable $fileWriter,
        iterable $fileReader
    ) {
        $this->fileWriter = $fileWriter;
        $this->fileReader = $fileReader;
    }

    public function getFileReader(string $format): FileReader
    {
        foreach ($this->fileReader as $reader) {
            if ($reader->supports($format)) {
                return $reader;
            }
        }

        throw new \RuntimeException('File reader ' . $format . ' does not exist.');
    }

    public function getFileWriter(string $format): FileWriter
    {
        foreach ($this->fileWriter as $writer) {
            if ($writer->supports($format)) {
                return $writer;
            }
        }

        throw new \RuntimeException('File writer' . $format . ' does not exist.');
    }
}
