<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Structs\ImportRequest;

interface ImportServiceInterface
{
    public const UNPROCESSED_DATA_FILE_ENDING = '-swag.csv';

    /**
     * Prepares import session based on profile and input file
     * and delivers information on how many records to import.
     */
    public function prepareImport(ImportRequest $importRequest): int;

    /**
     * Processes import based on profile and session and will be called
     * many times based on batch size.
     *
     * @return \Generator<string, int>
     */
    public function import(ImportRequest $importRequest, Session $session): \Generator;

    /**
     * Prepares the import for unprocessed data like product images.
     * Use the returned data to start a new import session.
     *
     * @return array{importFile: string, profileId: int, count: int, position: 0, load: true}|null
     */
    public function prepareImportOfUnprocessedData(ImportRequest $request): ?array;
}
