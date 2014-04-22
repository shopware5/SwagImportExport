<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class CsvFileWriter implements FileWriter
{

    private $treeStructure = false;

    public function writeHeader($fileName, $headerData)
    {
        $str = @file_put_contents($fileName, $headerData);
        if ($str === false) {
            throw new Exception("Cannot write in '$fileName'");
        }
    }

    public function writeRecords($fileName, $data)
    {
        $str = @file_put_contents($fileName, $data, FILE_APPEND);
        if ($str === false) {
            throw new Exception("Cannot write in '$fileName'");
        }
    }

    public function writeFooter($fileName, $footerData)
    {
        
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

}
