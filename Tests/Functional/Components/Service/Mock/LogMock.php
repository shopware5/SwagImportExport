<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Service\Mock;

use SwagImportExport\Components\Logger\LogDataStruct;
use SwagImportExport\Components\Logger\LoggerInterface;
use SwagImportExport\Components\Session\Session;

class LogMock implements LoggerInterface
{
    /**
     * @var array<array<string>|string>
     */
    private array $logs;

    /**
     * @param array<string> $messages
     */
    public function write(array $messages, string $status, Session $session): void
    {
        $this->logs[] = $messages;
    }

    public function logProcessing(string $writeStatus, string $filename, string $profileName, string $logMessage, string $status, Session $session): void
    {
        $logDataStruct = new LogDataStruct(
            \date('Y-m-d H:i:s'),
            $filename,
            $profileName,
            $logMessage,
            $status
        );

        $this->writeToFile($logDataStruct);
    }

    public function writeToFile(LogDataStruct $logDataStruct): void
    {
        $this->logs[] = $logDataStruct->getMessages();
    }

    /**
     * @return array<array<string>|string>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }
}
