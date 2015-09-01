<?php

namespace Shopware\Components\SwagImportExport\Logger;

use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\FileIO\FileWriter;
use Shopware\CustomModels\ImportExport\Logger as LoggerEntity;
use Shopware\CustomModels\ImportExport\Repository;
use Shopware\CustomModels\ImportExport\Session;

class Logger
{
    /**
     * @var ModelManager $manager
     */
    protected $manager;

    /**
     * @var Repository $loggerRepository
     */
    protected $loggerRepository;

    /**
     * @var LoggerEntity $loggerEntity
     */
    protected $loggerEntity;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * @var FileWriter $fileWriter
     */
    protected $fileWriter;

    /**
     * @param Session $session
     * @param FileWriter $fileWriter
     */
    public function __construct(Session $session, $fileWriter)
    {
        $this->session = $session;
        $this->fileWriter = $fileWriter;
    }

    /**
     * Returns entity manager
     *
     * @return ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    /**
     * @return LoggerEntity
     */
    public function getLoggerEntity()
    {
        if ($this->loggerEntity === null) {
            //fixes doctrine clear bug
            $loggerId = $this->session->getLogger()->getId();
            $this->loggerEntity = $this->getLoggerRepository()->find($loggerId);
        }

        return $this->loggerEntity;
    }

    /**
     * @return FileWriter
     */
    public function getFileWriter()
    {
        return $this->fileWriter;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $logger = $this->getLoggerEntity();

        return $logger->getMessage();
    }

    /**
     * @param array|string $messages
     * @param string $status
     */
    public function write($messages, $status)
    {
        $logger = $this->getLoggerEntity();

        if (is_array($messages)) {
            $appendMsg = implode(';', $messages);
        } else {
            $appendMsg = $messages;
        }

        $message = $logger->getMessage();

        if ($message) {
            $newMessage = $message . "\n" . $appendMsg;
        } else {
            $newMessage = $appendMsg;
        }

        $logger->setMessage($newMessage);
        $logger->setCreatedAt('now');

        if ($status !== null) {
            $logger->setStatus($status);
        }

        $this->getManager()->persist($logger);
        $this->getManager()->flush();
    }

    /**
     * @param $data
     */
    public function writeToFile($data)
    {
        $file = $this->getLogFile();

        $this->getFileWriter()->writeRecords($file, array($data));
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        if ($this->getPluginBootstrap()->checkMinVersion('5.1.0')) {
            $file = Shopware()->DocPath() . 'var/log/importexport.log';
        } else {
            $file = Shopware()->DocPath() . 'logs/importexport.log';
        }

        if (!file_exists($file)) {
            $columns = array('date/time', 'file', 'profile', 'message', 'successFlag');

            $this->getFileWriter()->writeHeader($file, $columns);
        }

        return $file;
    }

    /**
     * Helper Method to get access to the session repository.
     *
     * @return Repository
     */
    public function getLoggerRepository()
    {
        if ($this->loggerRepository === null) {
            $this->loggerRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Logger');
        }

        return $this->loggerRepository;
    }

    /**
     * @return \Shopware_Plugins_Backend_SwagImportExport_Bootstrap
     */
    private function getPluginBootstrap()
    {
        return Shopware()->Plugins()->Backend()->SwagImportExport();
    }
}
