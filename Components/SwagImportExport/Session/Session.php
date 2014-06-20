<?php

namespace Shopware\Components\SwagImportExport\Session;

use Shopware\CustomModels\ImportExport\Session as SessionEntity;
use Shopware\Components\SwagImportExport\Profile\Profile as Profile;
use Shopware\Components\SwagImportExport\DataIO as DataIO;

class Session
{

    /**
     * @var Shopware\CustomModels\ImportExport\Session
     */
    protected $sessionEntity;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    protected $manager;

    public function __construct(SessionEntity $session)
    {
        $this->sessionEntity = $session;
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this->sessionEntity, $method)) {
            return $this->sessionEntity->$method($arguments);
        } else {
            throw new \Exception("Method $method does not exists.");
        }
    }

    /**
     * Returns session entity
     * 
     * @return Shopware\CustomModels\ImportExport\Session
     */
    public function getEntity()
    {
        return $this->sessionEntity;
    }

    /**
     * Check if the session contains ids.
     * If the session has no ids, then the db adapter must be used to retrieve them.
     * Then writes these ids to the session and sets the session state to "active".
     * For now we will write the ids as a serialized array.
     */
    public function start(Profile $profile, array $data)
    {
        $sessionEntity = $this->getEntity();
        
        if ($data['totalCountedIds'] > 0) {
                //set count
                $sessionEntity->setTotalCount($data['totalCountedIds']);            
        }
        //set ids
        $sessionEntity->setIds($data['serializedIds']);

        //set type
        $sessionEntity->setType($data['type']);
        
        //set position
        $sessionEntity->setPosition(0);

        $dateTime = new \DateTime('now');

        //set date/time
        $sessionEntity->setCreatedAt($dateTime);

        if (!isset($data['fileName'])){
            throw new \Exception('Invalid file name.');
        }
        
        //set fileName
        $sessionEntity->setFileName($data['fileName']);

        if (!isset($data['format'])){
            throw new \Exception('Invalid format.');
        }
        
        //set format
        $sessionEntity->setFormat($data['format']);

        //change state
        $sessionEntity->setState('active');

        //set profile
        $sessionEntity->setProfile($profile->getEntity());

        $this->getManager()->persist($sessionEntity);

        $this->getManager()->flush();
    }

    /**
     * Checks if the number of processed records has reached the current max records count.
     * If reached then the session state will be set to "stopped"
     * Updates the session position with the current position (stored in a member variable).
     *
     */
    public function progress($step)
    {
        $sessionEntity = $this->getEntity();

        $position = $sessionEntity->getPosition();
        $count = $sessionEntity->getTotalCount();

        $newPosition = $position + $step;

        if ($newPosition >= $count) {
            $sessionEntity->setState('finished');
            $sessionEntity->setPosition($count);
        } else {
            $sessionEntity->setPosition($newPosition);
        }

        $this->getManager()->persist($sessionEntity);

        $this->getManager()->flush();
    }

    /**
     * Checks also the current position - if all the ids of the session are done, then the function does nothing.
     * Otherwise it sets the session state from "suspended" to "active", so that it is ready again for processing.
     */
    public function resume(DataIO $dataIO)
    {
        $sessionEntity = $this->getEntity();

        $recordIds = $sessionEntity->getIds();
        
        $sessionEntity->setState('active');

        $this->getManager()->persist($sessionEntity);

        $this->getManager()->flush();
        
        $data = array(
            'recordIds' => unserialize($recordIds),
            'fileName' => $sessionEntity->getFileName(),
        );
        
        return $data;
    }

    /**
     * Marks the session as closed (sets the session state as "closed").
     * If the session progress has not reached to the end, throws an exception.
     */
    public function close()
    {
        $sessionEntity = $this->getEntity();
        $sessionEntity->setState('closed');

        $this->getManager()->persist($sessionEntity);

        $this->getManager()->flush();
    }

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

    public function getSessionPosition()
    {
        return $this->sessionEntity->getPosition();
    }

    /**
     * Returns the state of the session. 
     * active: 
     *     Session is running and we can read/write records.
     * stopped: 
     *     Session is stopped because we have reached the max number of records per operation.
     * new: 
     *     Session is brand new and still has no records ids. 
     * finished: 
     *     Session is finished but the output file is still not finished (in case of export) 
     *     or the final db save is yet not performed (in case of import). 
     * closed: 
     *     Session is closed, file is fully exported/imported
     * 
     * @return string
     */
    public function getState()
    {
        return $this->sessionEntity->getState();
    }

    public function setTotalCount($totalCount)
    {
        $this->sessionEntity->setTotalCount($totalCount);
    }
//    
//    public function getFileName()
//    {
//        return $this->sessionEntity->getFileName();
//    }
}
