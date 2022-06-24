<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Logger;

use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\FileIO\FileWriter;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Models\Logger as LoggerEntity;
use SwagImportExport\Models\LoggerRepository;

class Logger implements LoggerInterface
{
    protected ModelManager $modelManager;

    protected LoggerRepository $loggerRepository;

    protected FileWriter $fileWriter;

    protected string $logDirectory;

    public function __construct(FileWriter $fileWriter, ModelManager $modelManager, string $logDirectory)
    {
        $this->fileWriter = $fileWriter;
        $this->modelManager = $modelManager;
        $this->logDirectory = $logDirectory;
        $this->loggerRepository = $this->modelManager->getRepository(LoggerEntity::class);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $messages, string $status, Session $session): void
    {
        $loggerModel = new LoggerEntity();

        $messages = \implode(';', $messages);
        $loggerModel->setSession($session->getEntity());
        $loggerModel->setMessage($messages);
        $loggerModel->setCreatedAt();
        $loggerModel->setStatus($status);

        $this->modelManager->persist($loggerModel);
        $this->modelManager->flush();
    }

    public function logProcessing(string $writeStatus, string $filename, string $profileName, string $logMessage, string $status, Session $session): void
    {
        $this->write([$logMessage], $writeStatus, $session);

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
        $file = $this->getLogFile();
        $this->fileWriter->writeRecords($file, [$logDataStruct->toArray()]);
    }

    private function getLogFile(): string
    {
        $filePath = $this->logDirectory . '/importexport.log';

        if (!\file_exists($filePath)) {
            $this->createLogFile($filePath);
        }

        return $filePath;
    }

    private function createLogFile(string $filePath): void
    {
        $columns = ['date/time', 'file', 'profile', 'message', 'successFlag'];
        $this->fileWriter->writeHeader($filePath, $columns);
    }
}
