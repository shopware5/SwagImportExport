<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Files\CsvFileWriter;
use Shopware\Components\SwagImportExport\Files\XmlFileWriter;
use Shopware\Components\SwagImportExport\Files\ExcelFileWriter;

class FileIOFactory extends \Enlight_Class implements \Enlight_Hook
{

    public function createFileReader($params)
    {
        
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
                throw new \Exception('File writer does not exists.');
        }
    }

}
