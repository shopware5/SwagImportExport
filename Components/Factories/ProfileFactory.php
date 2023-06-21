<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use Shopware\Components\Model\Exception\ModelNotFoundException;
use Shopware\Components\Model\ModelManager;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Utils\TreeHelper;
use SwagImportExport\Models\Profile as ProfileEntity;

class ProfileFactory implements \Enlight_Hook
{
    private ModelManager $modelManager;

    private \Enlight_Event_EventManager $eventManager;

    public function __construct(ModelManager $modelManager, \Enlight_Event_EventManager $eventManager)
    {
        $this->modelManager = $modelManager;
        $this->eventManager = $eventManager;
    }

    public function loadProfile(int $profileId): Profile
    {
        $profileEntity = $this->modelManager->getRepository(ProfileEntity::class)->findOneBy(['id' => $profileId]);

        if (!$profileEntity instanceof ProfileEntity) {
            throw new ModelNotFoundException(ProfileEntity::class, $profileId);
        }

        return new Profile($profileEntity);
    }

    public function loadProfileByFileName(string $filename): ?Profile
    {
        $repository = $this->modelManager->getRepository(ProfileEntity::class);

        foreach (\explode('.', $filename) as $part) {
            $part = \strtolower($part);
            $profileEntity = $repository->findOneBy(['name' => $part]);

            if ($profileEntity !== null) {
                return new Profile($profileEntity);
            }
        }

        return null;
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
            throw new \RuntimeException('Profile name is required');
        }
        if (!isset($data['type'])) {
            throw new \RuntimeException('Profile type is required');
        }

        if (!empty($data['hidden'])) {
            $tree = TreeHelper::getTreeByHiddenProfileType($data['type']);
        } elseif (isset($data['baseProfile'])) {
            $tree = $this->getDefaultTreeByBaseProfile($data['baseProfile']);
        } else {
            $tree = TreeHelper::getDefaultTreeByProfileType($data['type']);
        }

        $profileEntity = new ProfileEntity();
        $profileEntity->setName($data['name']);
        $profileEntity->setBaseProfile($data['baseProfile'] ?? null);
        $profileEntity->setType($data['type']);
        $profileEntity->setTree($tree);

        if (isset($data['hidden'])) {
            $profileEntity->setHidden((bool) $data['hidden']);
        }

        $this->modelManager->persist($profileEntity);
        $this->modelManager->flush();

        return $profileEntity;
    }

    public function loadHiddenProfile(string $type): Profile
    {
        $profileEntity = $this->modelManager->getRepository(ProfileEntity::class)->findOneBy(['type' => $type, 'hidden' => 1]);

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

    private function getDefaultTreeByBaseProfile(int $baseProfileId): string
    {
        return $this->modelManager
            ->getRepository(ProfileEntity::class)
            ->createQueryBuilder('p')
            ->select('p.tree')
            ->where('p.id = :baseProfileId')
            ->setParameter('baseProfileId', $baseProfileId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
