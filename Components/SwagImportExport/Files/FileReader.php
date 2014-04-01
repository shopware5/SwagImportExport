<?php

namespace Shopware\Components\SwagImportExport\Files;

interface FileReader
{
    public function readHeader($fileName);
    public function readRecords($fileName, $position, $count);
    public function readFooter($fileName);
}
