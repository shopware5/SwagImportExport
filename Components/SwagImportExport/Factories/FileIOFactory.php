<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\FileIO\XmlFileWriter;
use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\XmlFileReader;
use Shopware\Components\SwagImportExport\Utils\FileHelper;

class FileIOFactory extends \Enlight_Class implements \Enlight_Hook
{
    /**
     * @param string $format
     * @return CsvFileReader|XmlFileReader
     * @throws \Exception
     */
    public function createFileReader($format)
    {
        $fileHelper = new FileHelper();

        switch ($format) {
            case 'csv':
                return Shopware()->Container()->get('swag_import_export.csv_file_reader');
            case 'xml':
                return new XmlFileReader($fileHelper);
            default:
                throw new \Exception('File reader ' . $format . ' does not exists.');
        }
    }

    /**
     * @param string $format
     * @return CsvFileWriter|XmlFileWriter
     * @throws \Exception
     */
    public function createFileWriter($format)
    {
        $fileHelper = new FileHelper();

        switch ($format) {
            case 'csv':
                return Shopware()->Container()->get('swag_import_export.csv_file_writer');
            case 'xml':
                return new XmlFileWriter($fileHelper);
            default:
                throw new \Exception('File writer' . $format . ' does not exists.');
        }
    }
}
