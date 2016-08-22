<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Logger;

interface LoggerInterface
{
    /**
     * Returns the message of the log entity.
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Writes a log entry to the database.
     *
     * @param string|array $messages
     * @param string $status
     */
    public function write($messages, $status);

    /**
     * Writes a log entry to the import/export log file.
     *
     * @param LogDataStruct $logDataStruct
     */
    public function writeToFile(LogDataStruct $logDataStruct);
}
