<?php
declare(strict_types=1);
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
     */
    public function writeHeader(string $fileName, array $headerData): void;

    /**
     * @param array<mixed> $treeData
     */
    public function writeRecords(string $fileName, array $treeData): void;

    /**
     * @param array<mixed> $footerData
     */
    public function writeFooter(string $fileName, ?array $footerData): void;

    public function hasTreeStructure(): bool;
}
