<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Logger;

use Shopware\CustomModels\ImportExport\Session;

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
     * @param Session $session
     */
    public function write($messages, $status, Session $session);

    /**
     * Writes a log entry to the import/export log file.
     *
     * @param LogDataStruct $logDataStruct
     */
    public function writeToFile(LogDataStruct $logDataStruct);
}
