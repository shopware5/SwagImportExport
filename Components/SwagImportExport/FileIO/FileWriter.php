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
    /**
     * @param $fileName
     * @param $headerData
     *
     * @return mixed
     */
    public function writeHeader($fileName, $headerData);

    /**
     * @param $fileName
     * @param $treeData
     *
     * @return mixed
     */
    public function writeRecords($fileName, $treeData);

    /**
     * @param $fileName
     * @param $footerData
     *
     * @return mixed
     */
    public function writeFooter($fileName, $footerData);

    /**
     * @return bool
     */
    public function hasTreeStructure();
}
