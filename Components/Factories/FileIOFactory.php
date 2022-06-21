<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use SwagImportExport\Components\FileIO\CsvFileReader;
use SwagImportExport\Components\FileIO\CsvFileWriter;
use SwagImportExport\Components\FileIO\FileReader;
use SwagImportExport\Components\FileIO\FileWriter;
use SwagImportExport\Components\FileIO\XmlFileReader;
use SwagImportExport\Components\FileIO\XmlFileWriter;

class FileIOFactory extends \Enlight_Class implements \Enlight_Hook
{
    private CsvFileReader $csvFileReader;

    private CsvFileWriter $csvFileWriter;

    private XmlFileWriter $xmlFileWriter;

    private XmlFileReader $xmlFileReader;

    public function __construct(
        CsvFileReader $csvFileReader,
        CsvFileWriter $csvFileWriter,
        XmlFileReader $xmlFileReader,
        XmlFileWriter $xmlFileWriter
    ) {
        $this->csvFileReader = $csvFileReader;
        $this->csvFileWriter = $csvFileWriter;
        $this->xmlFileWriter = $xmlFileWriter;
        $this->xmlFileReader = $xmlFileReader;
    }

    public function createFileReader(string $format): FileReader
    {
        switch ($format) {
            case 'csv':
                return $this->csvFileReader;
            case 'xml':
                return $this->xmlFileReader;
            default:
                throw new \Exception('File reader ' . $format . ' does not exists.');
        }
    }

    public function createFileWriter(string $format): FileWriter
    {
        switch ($format) {
            case 'csv':
                return $this->csvFileWriter;
            case 'xml':
                return $this->xmlFileWriter;
            default:
                throw new \Exception('File writer' . $format . ' does not exists.');
        }
    }
}
