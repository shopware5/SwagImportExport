<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

interface FileWriter
{
    /**
     * @param array<mixed> $headerData
     *
     * @return void
     */
    public function writeHeader(string $fileName, array $headerData);

    /**
     * @param array<mixed> $treeData
     *
     * @return void
     */
    public function writeRecords(string $fileName, array $treeData);

    /**
     * @param array<mixed> $footerData
     *
     * @return void
     */
    public function writeFooter(string $fileName, ?array $footerData);

    /**
     * @return bool
     */
    public function hasTreeStructure();
}
