<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Utils\TreeHelper;
use SwagImportExport\CustomModels\Profile as ProfileEntity;

class ProfileFactory extends \Enlight_Class implements \Enlight_Hook
{
    private ModelManager $modelManager;

    private \Enlight_Event_EventManager $eventManager;

    public function __construct(
        ModelManager $modelManager,
        \Enlight_Event_EventManager $eventManager
    ) {
        $this->modelManager = $modelManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @param array{profileId?: int} $params
     */
    public function loadProfile(array $params): Profile
    {
        if (!isset($params['profileId'])) {
            throw new \Exception('Profile id is empty');
        }

        $profileRepository = $this->modelManager->getRepository(ProfileEntity::class);
        $profileEntity = $profileRepository->findOneBy(['id' => $params['profileId']]);

        if (!$profileEntity instanceof ProfileEntity) {
            throw new \Exception('Profile does not exists');
        }

        return new Profile($profileEntity);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createProfileModel(array $data): ProfileEntity
    {
        $event = $this->eventManager->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateProfileModel',
            ['subject' => $this, 'data' => $data]
        );

        if ($event instanceof \Enlight_Event_EventArgs && $event->getReturn() instanceof ProfileEntity) {
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
        } elseif (isset($data['baseProfile'])) {
            $tree = TreeHelper::getDefaultTreeByBaseProfile($data['baseProfile']);
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

    public function loadHiddenProfile(string $type): Profile
    {
        $profileRepository = $this->modelManager->getRepository(ProfileEntity::class);
        $profileEntity = $profileRepository->findOneBy(['type' => $type, 'hidden' => 1]);

        if (!$profileEntity instanceof ProfileEntity) {
            $data = [
                'name' => $type . 'Shopware',
                'type' => $type,
                'hidden' => 1,
            ];
            $profileEntity = $this->createProfileModel($data);
        }

        return new Profile($profileEntity);
    }
}
