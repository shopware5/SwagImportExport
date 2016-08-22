<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Logger;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\FileIO\FileWriter;
use Shopware\CustomModels\ImportExport\Logger as LoggerEntity;
use Shopware\CustomModels\ImportExport\Repository;

class Logger implements LoggerInterface
{
    /**
     * @var ModelManager $modelManager
     */
    protected $modelManager;

    /**
     * @var Repository $loggerRepository
     */
    protected $loggerRepository;

    /**
     * @var LoggerEntity $loggerEntity
     */
    protected $loggerEntity;

    /**
     * @var FileWriter $fileWriter
     */
    protected $fileWriter;

    /**
     * @param FileWriter $fileWriter
     * @param ModelManager $modelManager
     */
    public function __construct(FileWriter $fileWriter, ModelManager $modelManager)
    {
        $this->fileWriter = $fileWriter;
        $this->modelManager = $modelManager;
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
     * @param array|string $messages
     * @param string $status
     */
    public function write($messages, $status)
    {
        $loggerModel = new LoggerEntity();

        if (!is_array($messages)) {
            $messages = [ $messages ];
        }

        $messages = implode(';', $messages);
        $loggerModel->setMessage($messages);
        $loggerModel->setCreatedAt('now');
        $loggerModel->setStatus($status);

        $this->modelManager->persist($loggerModel);
        $this->modelManager->flush();
    }

    /**
     * @param LogDataStruct $logDataStruct
     */
    public function writeToFile(LogDataStruct $logDataStruct)
    {
        $file = $this->getLogFile();
        $this->fileWriter->writeRecords($file, array($logDataStruct->toArray()));
    }

    /**
     * @return string
     */
    private function getLogFile()
    {
        $filePath = Shopware()->DocPath() . 'var/log/importexport.log';

        if (!file_exists($filePath)) {
            $this->createLogFile($filePath);
        }

        return $filePath;
    }

    /**
     * @param string $filePath
     */
    private function createLogFile($filePath)
    {
        $columns = array('date/time', 'file', 'profile', 'message', 'successFlag');
        $this->fileWriter->writeHeader($filePath, $columns);
    }
}
