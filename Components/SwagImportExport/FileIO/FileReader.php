<?php

namespace Shopware\Components\SwagImportExport\FileIO;

interface FileReader
{
    public function readHeader($fileName);

    public function readRecords($fileName, $position, $count);

    public function readFooter($fileName);
}
