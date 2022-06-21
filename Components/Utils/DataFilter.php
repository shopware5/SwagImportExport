<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

class DataFilter
{
    /**
     * @var array<string, mixed>
     */
    private array $filter;

    /**
     * @param array<string, mixed> $filter
     */
    public function __construct(array $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilter(): array
    {
        return $this->filter;
    }
}
