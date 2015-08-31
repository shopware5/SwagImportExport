<?php

namespace Shopware\Components\SwagImportExport\FileIO;

interface FileWriter
{
    public function writeHeader($fileName, $headerData);

    public function writeRecords($fileName, $treeData);

    public function writeFooter($fileName, $footerData);
}
