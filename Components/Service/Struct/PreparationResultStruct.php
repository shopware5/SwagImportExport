<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service\Struct;

class PreparationResultStruct
{
    private int $position;

    private int $totalResultCount;

    public function __construct(int $position, int $totalResultCount)
    {
        $this->position = $position;
        $this->totalResultCount = $totalResultCount;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getTotalResultCount(): int
    {
        return $this->totalResultCount;
    }
}
