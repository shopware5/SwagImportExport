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
use Shopware\Components\SwagImportExport\FileIO\ExcelFileWriter;
use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\XmlFileReader;
use Shopware\Components\SwagImportExport\FileIO\ExcelFileReader;
use Shopware\Components\SwagImportExport\Utils\FileHelper;

class FileIOFactory extends \Enlight_Class implements \Enlight_Hook
{
    /**
     * @param $params
     * @param $fileHelper
     * @return CsvFileReader|ExcelFileReader|XmlFileReader
     * @throws \Exception
     */
    public function createFileReader($params, $fileHelper)
    {
        switch ($params['format']) {
            case 'csv':
                return new CsvFileReader($fileHelper);
            case 'xml':
                return new XmlFileReader($fileHelper);
            case 'excel':
                return new ExcelFileReader($fileHelper);
            default:
                throw new \Exception('File reader ' . $params['format'] . ' does not exists.');
        }
    }

    /**
     * @param $params
     * @param $fileHelper
     * @return CsvFileWriter|ExcelFileWriter|XmlFileWriter
     * @throws \Exception
     */
    public function createFileWriter($params, $fileHelper)
    {
        switch ($params['format']) {
            case 'csv':
                return new CsvFileWriter($fileHelper);
            case 'xml':
                return new XmlFileWriter($fileHelper);
            case 'excel':
                return new ExcelFileWriter($fileHelper);
            default:
                throw new \Exception('File writer' . $params['format'] . ' does not exists.');
        }
    }

    /**
     * @return FileHelper
     */
    public function createFileHelper()
    {
        return new FileHelper();
    }
}
