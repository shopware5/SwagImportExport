<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class CsvFileWriter implements FileWriter
{
    private $treeStructure = false;
    
    public function writeHeader($fileName, $headerDara)
    {
        
    }

    public function writeRecords($fileName, $headerDara)
    {
        
    }

    public function writeFooter($fileName, $headerDara)
    {
        
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

}
