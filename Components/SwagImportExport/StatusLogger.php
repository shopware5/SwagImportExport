<?php

namespace Shopware\Components\SwagImportExport;

use \Shopware\CustomModels\ImportExport\Logger as Logger;

class StatusLogger
{
    
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $manager;
    
    /**
     * Returns entity manager
     * 
     * @return Shopware\Components\Model\ModelManager
     */
    public function getManager()
    {
        if ($this->manager === null) {
            $this->manager = Shopware()->Models();
        }

        return $this->manager;
    }
    
    /**
     * Create log on complete or failure import/export
     * 
     * @param string $message
     * @param string $status
     */
    public function write($message, $status)
    {
        $newLog = new Logger();
        $newLog->setMessage($message);
        $newLog->setStatus($status);
        $newLog->setCreateAt(new \DateTime());

        $this->getManager()->persist($newLog);
        $this->getManager()->flush();
    }
       
}
