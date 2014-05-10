<?php

namespace Shopware\Components\SwagImportExport\Profile;

class Profile
{

    private $profileEntity;
    
    /**
     * @var array 
     */
    private $configNames;

    public function __construct(Shopware\Components\SwagImportExport\Profile\Profile $profile)
    {
        $this->profileEntity = $profile;
        $this->configNames = array('exportConversion', 'tree');
    }

    public function getType()
    {
        return $this->profileEntity->getType();
    }
    
    public function getName()
    {
        return $this->profileEntity->getName();
    }

    public function getConfigNames()
    {
        return $this->configNames;
    }

    public function getConfig($name)
    {
        switch ($name) {
            case 'exportConversion':
                return $this->profileEntity->getExpressions();
            case 'tree':
                return $this->profileEntity->getTree();    
            default:
                throw new \Exception('Config does not exists');
        }
    }
}