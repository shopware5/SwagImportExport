<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters;

interface UnprocessedDataDbAdapter
{
    /**
     * Returns unprocessed data. This will be used every time if an import wants to create data which relies on created data.
     * For instance article images, similar or accessory articles.
     *
     * @return array<mixed>
     */
    public function getUnprocessedData(): array;
}
