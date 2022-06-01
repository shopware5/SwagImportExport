<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Logger;

use SwagImportExport\CustomModels\Session;

interface LoggerInterface
{
    /**
     * Returns the message of the log entity.
     */
    public function getMessage(): ?string;

    /**
     * Writes a log entry to the database.
     */
    public function write(array $messages, string $status, Session $session): void;

    /**
     * Writes a log entry to the import/export log file.
     */
    public function writeToFile(LogDataStruct $logDataStruct): void;
}
