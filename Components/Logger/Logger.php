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
use SwagImportExport\CustomModels\Logger as LoggerEntity;
use SwagImportExport\CustomModels\LoggerRepository;
use SwagImportExport\CustomModels\Session;

class Logger implements LoggerInterface
{
    protected ModelManager $modelManager;

    protected LoggerRepository $loggerRepository;

    protected ?LoggerEntity $loggerEntity = null;

    protected FileWriter $fileWriter;

    protected string $logDirectory;

    public function __construct(FileWriter $fileWriter, ModelManager $modelManager, string $logDirectory)
    {
        $this->fileWriter = $fileWriter;
        $this->modelManager = $modelManager;
        $this->logDirectory = $logDirectory;
        $this->loggerRepository = $this->modelManager->getRepository(LoggerEntity::class);
    }

    public function getMessage(): ?string
    {
        if (!$this->loggerEntity) {
            return null;
        }

        return $this->loggerEntity->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $messages, string $status, Session $session): void
    {
        $loggerModel = new LoggerEntity();

        $messages = \implode(';', $messages);
        $loggerModel->setSession($session);
        $loggerModel->setMessage($messages);
        $loggerModel->setCreatedAt();
        $loggerModel->setStatus($status);

        $this->modelManager->persist($loggerModel);
        $this->modelManager->flush();
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
