<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

interface FileReader
{
    public function supports(string $format): bool;

    public function readRecords(string $fileName, int $position, int $step): array;

    public function getTotalCount(string $fileName): int;

    /**
     * @param array<string, array|string> $tree
     */
    public function setTree(array $tree): void;

    public function hasTreeStructure(): bool;
}
