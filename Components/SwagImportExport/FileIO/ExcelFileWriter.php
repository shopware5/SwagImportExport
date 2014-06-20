<?php

namespace Shopware\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\Utils\FileHelper;

class ExcelFileWriter implements FileWriter
{

    /**
     * @var $fileHelper 
     */
    protected $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

    public function writeHeader($fileName, $headerDara)
    {
        
    }

    public function writeRecords($fileName, $headerDara)
    {
        
    }

    public function writeFooter($fileName, $headerDara)
    {
        
    }

}
