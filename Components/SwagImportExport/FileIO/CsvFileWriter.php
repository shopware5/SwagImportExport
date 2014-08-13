<?php

namespace Shopware\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\Utils\FileHelper;

class CsvFileWriter implements FileWriter
{

    protected $treeStructure = false;

    /**
     * @var $fileHelper 
     */
    protected $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

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

        $this->getFileHelper()->writeStringToFile($fileName, $columnNames);
    }

    public function writeRecords($fileName, $data)
    {
        $convertor = new \Shopware_Components_Convert_Csv;
        $convertor->sSettings['encoding'] = 'UTF-8';
        $convertor->sSettings['newline'] = "\r\n";
        $flatData = $convertor->encode($data);

        $this->getFileHelper()->writeStringToFile($fileName, $flatData, FILE_APPEND);
    }

    public function writeFooter($fileName, $footerData)
    {
        
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    public function getFileHelper()
    {
        return $this->fileHelper;
    }

}
