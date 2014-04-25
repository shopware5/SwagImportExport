<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class CsvFileWriter implements FileWriter
{

    protected $treeStructure = false;

    /**
     * @param string $fileName
     * @param array $headerData
     * @throws \Exception
     * @throws \Exception
     */
    public function writeHeader($fileName, $headerData)
    {
        if (!is_array($headerData)) {
            throw new \Exception('Header data is not valid');
        }
        
        $columnNames .= implode(';', $headerData) . "\n";
                
        $str = @file_put_contents($fileName, $columnNames);
        if ($str === false) {
            throw new \Exception("Cannot write header in '$fileName'");
        }
    }

    public function writeRecords($fileName, $data)
    {
        $flatData = '';

        foreach ($data as $record) {
            $flatData .= implode(';', $record) . "\n";
        }
        
        $str = @file_put_contents($fileName, $flatData, FILE_APPEND);
        if ($str === false) {
            throw new Exception("Cannot write records in '$fileName'");
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
