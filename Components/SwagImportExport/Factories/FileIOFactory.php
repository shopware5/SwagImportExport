<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\FileIO\XmlFileWriter;
use Shopware\Components\SwagImportExport\FileIO\ExcelFileWriter;
use Shopware\Components\SwagImportExport\FileIO\CsvFileReader;
use Shopware\Components\SwagImportExport\FileIO\XmlFileReader;
use Shopware\Components\SwagImportExport\FileIO\ExcelFileReader;

class FileIOFactory extends \Enlight_Class implements \Enlight_Hook
{

    public function createFileReader($params)
    {
        switch ($params['format']) {
            case 'csv':
                return new CsvFileReader();
            case 'xml':
                return new XmlFileReader();
            case 'excel':
                return new ExcelFileReader();
            default:
                throw new \Exception('File reader '. $params['format'] . 'does not exists.');
        }
    }

    public function createFileWriter($params)
    {
        switch ($params['format']) {
            case 'csv':
                return new CsvFileWriter();
            case 'xml':
                return new XmlFileWriter();
            case 'excel':
                return new ExcelFileWriter();
            default:
                throw new \Exception('File writer' . $params['format'] . 'does not exists.');
        }
    }

}
