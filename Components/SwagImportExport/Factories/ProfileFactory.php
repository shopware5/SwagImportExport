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
use Shopware\CustomModels\ImportExport\Repository;

class ProfileFactory extends \Enlight_Class implements \Enlight_Hook
{
    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $modelManager;

    public function __construct()
    {
        parent::__construct();

        $this->modelManager = Shopware()->Models();
    }

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

        /** @var Repository $profileRepository */
        $profileRepository = $this->modelManager->getRepository(ProfileEntity::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $params['profileId']]);

        if (!$profileEntity) {
            throw new \Exception('Profile does not exists');
        }

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
            ['subject' => $this, 'data' => $data]
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
        } else {
            $tree = TreeHelper::getDefaultTreeByProfileType($data['type']);
        }

        $profileEntity = new ProfileEntity();
        $profileEntity->setName($data['name']);
        $profileEntity->setBaseProfile($data['baseProfile']);
        $profileEntity->setType($data['type']);
        $profileEntity->setTree($tree);

        if (isset($data['hidden'])) {
            $profileEntity->setHidden($data['hidden']);
        }

        $this->modelManager->persist($profileEntity);
        $this->modelManager->flush();

        return $profileEntity;
    }

    /**
     * @param $type
     * @return Profile
     * @throws \Exception
     */
    public function loadHiddenProfile($type)
    {
        /** @var Repository $profileRepository */
        $profileRepository = $this->modelManager->getRepository(ProfileEntity::class);
        $profileEntity = $profileRepository->findOneBy(['type' => $type, 'hidden' => 1]);

        if (!$profileEntity) {
            $data = [
                'name' => $type . 'Shopware',
                'type' => $type,
                'hidden' => 1
            ];
            $profileEntity = $this->createProfileModel($data);
        }

        return new Profile($profileEntity);
    }
}
