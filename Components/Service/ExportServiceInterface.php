<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\Service\Struct\PreparationResultStruct;

interface ExportServiceInterface
{
    /**
     * Prepares export session based on profile and delivers
     * information on how many records to export.\
     *
     * @param array<string, mixed> $requestData
     * @param array<string, mixed> $filterParams
     */
    public function prepareExport(array $requestData, array $filterParams): PreparationResultStruct;

    /**
     * Processes export based on profile and session and accepts special
     * filtering for several dataDbAdapters.
     *
     * @param array<string, mixed> $requestData
     * @param array<string, mixed> $filterParams
     *
     * @return array<string, mixed>
     */
    public function export(array $requestData, array $filterParams): array;
}
