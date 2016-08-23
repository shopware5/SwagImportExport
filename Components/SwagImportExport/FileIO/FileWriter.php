<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO;

interface FileWriter
{
    public function writeHeader($fileName, $headerData);

    public function writeRecords($fileName, $treeData);

    public function writeFooter($fileName, $footerData);
}
