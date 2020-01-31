<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

use Shopware\Components\SwagImportExport\Service\Struct\PreparationResultStruct;

interface ImportServiceInterface
{
    /**
     * Prepares import session based on profile and input file
     * and delivers information on how many records to import.
     *
     * @param string $inputFileName
     *
     * @throws \Exception
     *
     * @return PreparationResultStruct
     */
    public function prepareImport(array $requestData, $inputFileName);

    /**
     * Processes import based on profile and session and will be called
     * many times based on batch size.
     *
     * @param string $inputFile
     *
     * @throws \Exception
     *
     * @return array
     */
    public function import(array $requestData, array $unprocessedFiles, $inputFile);
}
