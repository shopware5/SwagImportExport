<?php

namespace Shopware\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\Utils\FileHelper;
use Shopware\Components\SwagImportExport\FileIO\Encoders\XmlEncoder;

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
     * @var XmlEncoder
     */
    protected $xmlConvertor;
    
    /**
     * @var FileHelper $fileHelper
     */
    protected $fileHelper;
    
    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

    /**
     * Writes the header data in the file. The header data should be in a tree-like structure.
     *
     * @param $fileName
     * @param $headerData
     * @throws \Exception
     */
    public function writeHeader($fileName, $headerData)
    {
        $dataParts = $this->splitHeaderFooter($headerData);
        $this->getFileHelper()->writeStringToFile($fileName, $dataParts[0]);
    }

    /**
     * Writes records in the file. The data must be a tree-like structure.
     * The header of the file must be already written on the harddisk,
     * otherwise the xml fill have an invalid format.
     *
     * @param $fileName
     * @param $data
     * @throws \Exception
     */
    public function writeRecords($fileName, $data)
    {
        //converting the whole template tree without the interation part
        $convertor = $this->getXmlConvertor();
        $data = $convertor->_encode($data);

        $this->getFileHelper()->writeStringToFile($fileName, trim($data), FILE_APPEND);
    }

    /**
     * Writes the footer data in the file. These are usually some closing tags -
     * they should be in a tree-like structure.
     *
     * @param $fileName
     * @param $footerData
     * @throws \Exception
     */
    public function writeFooter($fileName, $footerData)
    {
        $dataParts = $this->splitHeaderFooter($footerData);

        $data = isset($dataParts[1]) ? $dataParts[1] : null;

        $this->getFileHelper()->writeStringToFile($fileName, $data, FILE_APPEND);
    }

    /**
     * @return bool
     */
    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    /**
     * @return FileHelper
     */
    public function getFileHelper()
    {
        return $this->fileHelper;
    }

    /**
     * Splitting the tree into two parts
     *
     * @param array $data
     * @return string
     * @throws \Exception
     */
    protected function splitHeaderFooter($data)
    {
        //converting the whole template tree without the iteration part
        $convertor = $this->getXmlConvertor();
        $data = $convertor->encode($data);

        //spliting the the tree in to two parts
        $dataParts = explode('<_currentMarker></_currentMarker>', $data);

        return $dataParts;
    }

    /**
     * Returns XmlEncoder
     *
     * @return XmlEncoder
     */
    protected function getXmlConvertor()
    {
        if ($this->xmlConvertor === null) {
            $this->xmlConvertor = new XmlEncoder();
        }

        return $this->xmlConvertor;
    }
}
