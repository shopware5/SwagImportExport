<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\Profile\ProfileSerializer;

class ProfileFactory extends \Enlight_Class implements \Enlight_Hook
{

    private $profileId;
    private $profileEntity;
    private $profileRepository;

    public function loadProfile($params)
    {
        if (!isset($params['profileId'])) {
            throw new \Exception('Profile id is empty');
        }

        $profileEntity = $this->getProfileRepository()->findOneBy(array('id' => $params['profileId']));
        
        if (!$profileEntity) {
            throw new \Exception('Profile does not exists');
        }

        $this->profileId = $params['profileId'];

        return new Profile($profileEntity);
    }

    public function createProfileSerializer()
    {
        
    }

    public function createProfile()
    {
        
    }

    public function getProfileEntity()
    {
        if ($this->profileEntity == null) {
            $this->profileEntity = $this->getProfileRepository()->findOneBy(array('id' => $this->getProfileId()));
        }

        return $this->profileEntity;
    }

    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * Helper Method to get access to the profile repository.
     *
     * @return Shopware\CustomModels\ImportExport\Profile
     */
    public function getProfileRepository()
    {
        if ($this->profileRepository === null) {
            $this->profileRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Profile');
        }
        return $this->profileRepository;
    }

}
