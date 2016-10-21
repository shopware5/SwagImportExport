<?php

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

use Shopware\Components\SwagImportExport\Service\Struct\PreparationResultStruct;

/**
 * @package Shopware\Components\SwagImportExport\Service
 */
interface ExportServiceInterface
{
    /**
     * Prepares export session based on profile and delivers
     * information on how many records to export.
     *
     * @param array $requestData
     * @param array $filterParams
     * @return PreparationResultStruct
     * @throws \Exception
     */
    public function prepareExport(array $requestData, array $filterParams);

    /**
     * Processes export based on profile and session and accepts special
     * filtering for several dataDbAdapters.
     *
     * @param array $requestData
     * @param array $filterParams
     * @return array
     * @throws \Exception
     */
    public function export(array $requestData, array $filterParams);
}
