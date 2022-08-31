<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

interface DefaultHandleable
{
    /**
     * @param array<string, mixed> $values
     */
    public function setDefaultValues(array $values): void;
}
