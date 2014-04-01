<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Files\CsvFileWriter;
use Shopware\Components\SwagImportExport\Files\XmlFileWriter;
use Shopware\Components\SwagImportExport\Files\ExcelFileWriter;
use Shopware\Components\SwagImportExport\Files\CsvFileReader;
use Shopware\Components\SwagImportExport\Files\XmlFileReader;
use Shopware\Components\SwagImportExport\Files\ExcelFileReader;

class FileIOFactory extends \Enlight_Class implements \Enlight_Hook
{

    public function createFileReader($param)
    {
        switch ($param) {
            case 'csv':
                return new CsvFileReader();
            case 'xml':
                return new XmlFileReader();
            case 'excel':
                return new ExcelFileReader();
            default:
                throw new \Exception("File reader $param does not exists.");
        }
    }

    public function createFileWriter($param)
    {
        switch ($param) {
            case 'csv':
                return new CsvFileWriter();
            case 'xml':
                return new XmlFileWriter();
            case 'excel':
                return new ExcelFileWriter();
            default:
                throw new \Exception("File writer $param does not exists.");
        }
    }

}
