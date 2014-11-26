<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Utils\TreeHelper;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\Components\SwagImportExport\Profile\ProfileSerializer;
use Shopware\CustomModels\ImportExport\Profile as ProfileEntity;

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

    public function createProfileModel($data)
    {
        $event = Shopware()->Events()->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateProfileModel',
            array('subject' => $this, 'data' => $data)
        );

        if ($event && $event instanceof \Enlight_Event_EventArgs
                && $event->getReturn() instanceof ProfileEntity){
            return $event->getReturn();
        }

        if (isset($data['hidden']) && $data['hidden']) {
            $tree = TreeHelper::getTreeByHiddenProfileType($data['type']);
        } else {
            $tree = TreeHelper::getDefaultTreeByProfileType($data['type']);
        }

        $profileModel = new ProfileEntity();

        if (!isset($data['name'])) {
            throw new \Exception('Profile name is required');
        }
        $profileModel->setName($data['name']);

        if (!isset($data['type'])) {
            throw new \Exception('Profile type is required');
        }
        $profileModel->setType($data['type']);

        $profileModel->setTree($tree);

        if (isset($data['hidden'])) {
            $profileModel->setHidden($data['hidden']);
        }

        Shopware()->Models()->persist($profileModel);
        Shopware()->Models()->flush();

        return $profileModel;
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
     * @return \Shopware\CustomModels\ImportExport\Profile
     */
    public function getProfileRepository()
    {
        if ($this->profileRepository === null) {
            $this->profileRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Profile');
        }
        return $this->profileRepository;
    }

    public function loadHiddenProfile($type)
    {
        $profileModel = $this->getProfileRepository()->findOneBy(array('type' => $type, 'hidden' => 1));

        if (!$profileModel) {
            $data = array(
                'name' => $type . 'Shopware',
                'type' => $type,
                'hidden' => 1
            );
            $profileModel = $this->createProfileModel($data);
        }

        return new Profile($profileModel);
    }

}
