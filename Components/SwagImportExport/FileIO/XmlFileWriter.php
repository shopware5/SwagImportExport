<?php

namespace Shopware\Components\SwagImportExport\FileIO;

/**
 * This class is responsible to generate XML file or portions of an XML file on the hard disk.
 * The input data must be in php array forming a tree-like structure
 */
class XmlFileWriter implements FileWriter
{

    /**
     * @var boolean
     */
    protected $treeStructure = true;

    /**
     * @var Shopware_Components_Convert_Xml
     */
    protected $xmlConvertor;

    /**
     * Writes the header data in the file. The header data should be in a tree-like structure. 
     */
    public function writeHeader($fileName, $headerData)
    {
        $dataParts = $this->splitHeaderFooter($headerData);

        $str = @file_put_contents($fileName, $dataParts[0]);
        if ($str === false) {
            throw new \Exception("Cannot write in '$fileName'");
        }
    }

    /**
     * Writes records in the file. The data must be a tree-like structure.
     * The header of the file must be already written on the harddisk,
     * otherwise the xml fill have an invalid format.
     */
    public function writeRecords($fileName, $data)
    {
        //converting the whole template tree without the interation part
        $convertor = $this->getXmlConvertor();
        $data = $convertor->_encode($data);
        
        $str = @file_put_contents($fileName, trim($data), FILE_APPEND);
        if ($str === false) {
            throw new \Exception("Cannot write in '$fileName'");
        }
    }

    /**
     * Writes the footer data in the file. These are usually some closing tags - 
     * they should be in a tree-like structure.
     */
    public function writeFooter($fileName, $footerData)
    {
        $dataParts = $this->splitHeaderFooter($footerData);
        
        $data = isset($dataParts[1]) ? $dataParts[1] : null;
        
        $str = @file_put_contents($fileName, $data, FILE_APPEND);
        if ($str === false) {
            throw new \Exception("Cannot write in '$fileName'");
        }
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    /**
     * Spliting the tree into two parts
     * 
     * @param array $data
     * @return string
     * @throws \Exception
     */
    protected function splitHeaderFooter($data)
    {
        //converting the whole template tree without the interation part
        $convertor = $this->getXmlConvertor();
        $data = $convertor->encode($data);

        //spliting the the tree in to two parts
        $dataParts = explode('<_currentMarker></_currentMarker>', $data);

        return $dataParts;
    }

    /**
     * Returns Shopware_Components_Convert_Xml
     * 
     * @return object
     */
    protected function getXmlConvertor()
    {
        if ($this->xmlConvertor === null) {
            $this->xmlConvertor = new \Shopware_Components_Convert_Xml();
        }

        return $this->xmlConvertor;
    }

}
