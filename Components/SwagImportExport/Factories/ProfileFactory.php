<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Utils\TreeHelper;
use Shopware\Components\SwagImportExport\Profile\Profile;
use Shopware\CustomModels\ImportExport\Profile as ProfileEntity;

class ProfileFactory extends \Enlight_Class implements \Enlight_Hook
{
    private $profileId;
    /**
     * @var ProfileEntity $profileEntity
     */
    private $profileEntity;

    /**
     * @var \Shopware\CustomModels\ImportExport\Repository
     */
    private $profileRepository;

    /**
     * @param $params
     * @return Profile
     * @throws \Exception
     */
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

    /**
     * @param $data
     * @return mixed|ProfileEntity
     * @throws \Enlight_Exception
     * @throws \Exception
     */
    public function createProfileModel($data)
    {
        $event = Shopware()->Events()->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateProfileModel',
            array('subject' => $this, 'data' => $data)
        );

        if ($event && $event instanceof \Enlight_Event_EventArgs && $event->getReturn() instanceof ProfileEntity) {
            return $event->getReturn();
        }

        if (!isset($data['name'])) {
            throw new \Exception('Profile name is required');
        }
        if (!isset($data['type'])) {
            throw new \Exception('Profile type is required');
        }

        if (isset($data['hidden']) && $data['hidden']) {
            $tree = TreeHelper::getTreeByHiddenProfileType($data['type']);
        } else if (isset($data['baseProfile'])) {
            $tree = TreeHelper::getDefaultTreeByBaseProfile($data['baseProfile']);
        } else {
            $tree = TreeHelper::getDefaultTreeByProfileType($data['type']);
        }

        $profileModel = new ProfileEntity();
        $profileModel->setName($data['name']);
        $profileModel->setBaseProfile($data['baseProfile']);
        $profileModel->setType($data['type']);
        $profileModel->setTree($tree);

        if (isset($data['hidden'])) {
            $profileModel->setHidden($data['hidden']);
        }

        Shopware()->Models()->persist($profileModel);
        Shopware()->Models()->flush();

        return $profileModel;
    }

    /**
     * @return ProfileEntity
     */
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
     * @return \Shopware\CustomModels\ImportExport\Repository
     */
    public function getProfileRepository()
    {
        if ($this->profileRepository === null) {
            $this->profileRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Profile');
        }

        return $this->profileRepository;
    }

    /**
     * @param $type
     * @return Profile
     * @throws \Exception
     */
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
