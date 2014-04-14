<?php

namespace Shopware\Components\SwagImportExport\FileIO;

/**
 * This class is responsible to generate XML file or portions of an XML file on the hard disk.
 * The input data must be in php array forming a tree-like structure
 */
class XmlFileWriter implements FileWriter
{

    private $treeStructure = true;

    /**
     * Writes the header data in the file. The header data should be in a tree-like structure. 
     */
    public function writeHeader($fileName, $headerData)
    {
        
    }

    /**
     * Writes records in the file. The data must be a tree-like structure.
     * The header of the file must be already written on the harddisk,
     * otherwise the xml fill have an invalid format.
     */
    public function writeRecords($fileName, $data)
    {
        $str = @file_put_contents($fileName, $data);
        if ($str === false) {
            throw new Exception("Cannot write in '$fileName'");
        } 
    }

    /**
     * Writes the footer data in the file. These are usually some closing tags - 
     * they should be in a tree-like structure.
     */
    public function writeFooter($fileName, $footerData)
    {
        
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

}
