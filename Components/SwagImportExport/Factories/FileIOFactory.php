<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\FileIO\XmlFileReader;
use Shopware\Components\SwagImportExport\FileIO\XmlFileWriter;

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

    /**
     * @param string $format
     *
     * @throws \Exception
     *
     * @return CsvFileReader|XmlFileReader
     */
    public function createFileReader($format)
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

    /**
     * @param string $format
     *
     * @throws \Exception
     *
     * @return CsvFileWriter|XmlFileWriter
     */
    public function createFileWriter($format)
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
