<?php

namespace Shopware\Components\SwagImportExport\Files;

interface FileWriter
{
    public function writeHeader($fileName, $headerData);
    public function writeRecords($fileName, $treeData);
    public function writeFooter($fileName, $footerData);
}
