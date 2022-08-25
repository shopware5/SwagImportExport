<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Logger;

use SwagImportExport\Components\Session\Session;

interface LoggerInterface
{
    /**
     * Writes a log entry to the database.
     */
    public function write(array $messages, string $status, Session $session): void;

    /**
     * Write a log entry to the database and to the import/export log file.
     */
    public function logProcessing(string $writeStatus, string $filename, string $profileName, string $logMessage, string $status, Session $session): void;

    /**
     * Writes a log entry to the import/export log file.
     */
    public function writeToFile(LogDataStruct $logDataStruct): void;
}
