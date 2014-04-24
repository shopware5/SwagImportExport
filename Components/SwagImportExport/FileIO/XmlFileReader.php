<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class XmlFileReader implements FileReader
{

    private $treeStructure = true;

    public function readHeader($fileName)
    {
        
    }

    public function readRecords($fileName, $position, $count)
    {
        
    }

    public function readFooter($fileName)
    {
        
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }
}
