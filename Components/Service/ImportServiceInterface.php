<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\Service\Struct\PreparationResultStruct;

interface ImportServiceInterface
{
    /**
     * Prepares import session based on profile and input file
     * and delivers information on how many records to import.
     *
     * @param array<string, mixed> $requestData
     */
    public function prepareImport(array $requestData, string $inputFileName): PreparationResultStruct;

    /**
     * Processes import based on profile and session and will be called
     * many times based on batch size.
     *
     * @param array<string, mixed>  $requestData
     * @param array<string, string> $unprocessedFiles
     *
     * @return array<string, mixed>
     */
    public function import(array $requestData, array $unprocessedFiles, string $inputFile): array;
}
