<?php
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

    /**
     * @return string|null
     */
    public function getMessage()
    {
        if (!$this->loggerEntity) {
            return null;
        }

        return $this->loggerEntity->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $messages, string $status, Session $session)
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

    public function writeToFile(LogDataStruct $logDataStruct)
    {
        $file = $this->getLogFile();
        $this->fileWriter->writeRecords($file, [$logDataStruct->toArray()]);
    }

    /**
     * @return string
     */
    private function getLogFile()
    {
        $filePath = $this->logDirectory . '/importexport.log';

        if (!\file_exists($filePath)) {
            $this->createLogFile($filePath);
        }

        return $filePath;
    }

    private function createLogFile(string $filePath)
    {
        $columns = ['date/time', 'file', 'profile', 'message', 'successFlag'];
        $this->fileWriter->writeHeader($filePath, $columns);
    }
}
