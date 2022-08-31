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
use SwagImportExport\Components\Structs\ExportRequest;

interface ExportServiceInterface
{
    /**
     * Prepares export session based on profile and delivers
     * information on how many records to export.\
     */
    public function prepareExport(ExportRequest $request, Session $session): int;

    /**
     * Processes export based on profile and session and accepts special
     * filtering for several dataDbAdapters.
     *
     * @return \Generator<int>
     */
    public function export(ExportRequest $request, Session $session): \Generator;
}
