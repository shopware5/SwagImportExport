<?php

namespace Shopware\Components\SwagImportExport\Logger;

use \Shopware\CustomModels\ImportExport\Logger as LoggerEntity;
use Shopware\Components\SwagImportExport\Session\Session;

class Logger
{

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $loggerRepository;

    /**
     * @var LoggerEntity $loggerEntity
     */
    protected $loggerEntity;
    /**
     * @var Session
     */
    protected $session;
    protected $fileWriter;

    public function __construct(Session $session, $fileWriter)
    {
        $this->session = $session;
        $this->fileWriter = $fileWriter;
    }

    /**
     * Returns entity manager
     * 
     * @return \Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }

    public function getLoggerEntity()
    {
        if ($this->loggerEntity === null){
            //fixes doctrine clear bug
            $loggerId = $this->session->getLogger()->getId();
            $this->loggerEntity = $this->getLoggerRepository()->find($loggerId);
        }

        return $this->loggerEntity;
    }

    public function getFileWriter()
    {
        return $this->fileWriter;
    }

    public function getMessage()
    {
        $logger = $this->getLoggerEntity();

        return $logger->getMessage();
    }

    /**
     * @param $messages
     * @param $status
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

    public function writeToFile($data)
    {
        $file = $this->getLogFile();

        $this->getFileWriter()->writeRecords($file, array($data));
    }

    public function getLogFile()
    {
        $file = Shopware()->DocPath() . 'logs/importexport.log';
        if (!file_exists($file)) {
            $columns = array('date/time', 'file', 'profile', 'message', 'successFlag');

            $this->getFileWriter()->writeHeader($file, $columns);
        }

        return $file;
    }

    /**
     * Helper Method to get access to the session repository.
     *
     * @return \Shopware\CustomModels\ImportExport\Session
     */
    public function getLoggerRepository()
    {
        if ($this->loggerRepository === null) {
            $this->loggerRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Logger');
        }
        return $this->loggerRepository;
    }

}
