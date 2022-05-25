<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

interface FileReader
{
    /**
     * @return array
     */
    public function readRecords(string $fileName, int $position, int $count);

    /**
     * @return int
     */
    public function getTotalCount(string $fileName);

    /**
     * @param array<string, array|string> $tree
     *
     * @return void
     */
    public function setTree(array $tree);

    /**
     * @return bool
     */
    public function hasTreeStructure();
}
