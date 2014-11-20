<?php

namespace Shopware\Components\SwagImportExport\Logger;

use \Shopware\CustomModels\ImportExport\Logger as LoggerEntity;

class Logger
{

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $manager;
    protected $loggerRepository;

    /**
     * @var LoggerEntity $logger
     */
    protected $logger;

    public function __construct(LoggerEntity $logger)
    {
        $this->logger = $logger;
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

    public function getLogger()
    {
        return $this->logger;
    }

    public function getMessage()
    {
        $logger = $this->getLogger();

        return $logger->getMessage();
    }

    /**
     * @param $messages
     * @param $status
     */
    public function write($messages, $status)
    {
        if (!$messages) {
            return;
        }

        $logger = $this->getLogger();

        if (is_array($messages)) {
            $appendMsg = implode(';', $messages);
        } else {
            $appendMsg = $messages;
        }

        $message = $logger->getMessage();

        if ($message) {
            $newMessage = $message . '; ' . $appendMsg;
        } else {
            $newMessage = $appendMsg;
        }

        $logger->setMessage($newMessage);
        $logger->setCreatedAt('now');

        if ($status !== null) {
            $logger->setStatus($status);
        }

        $this->getManager()->merge($logger);
        $this->getManager()->flush();
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
