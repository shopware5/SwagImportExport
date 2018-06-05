<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO;

interface FileReader
{
    /**
     * @param string $fileName
     * @param int    $position
     * @param int    $count
     *
     * @return array
     */
    public function readRecords($fileName, $position, $count);

    /**
     * @param string $fileName
     *
     * @return int
     */
    public function getTotalCount($fileName);

    /**
     * @param array $tree
     */
    public function setTree($tree);

    /**
     * @return bool
     */
    public function hasTreeStructure();
}
