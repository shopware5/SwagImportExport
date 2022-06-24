<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Profile;

use SwagImportExport\CustomModels\Profile as ProfileEntity;

class Profile
{
    private const DEFAULT_CONFIG_NAMES = ['exportConversion', 'tree', 'decimals'];

    private ProfileEntity $profileEntity;

    private array $configNames;

    public function __construct(ProfileEntity $profile, array $configNames = [])
    {
        $this->profileEntity = $profile;
        $this->configNames = $configNames ?: self::DEFAULT_CONFIG_NAMES;
    }

    public function getId(): int
    {
        return $this->profileEntity->getId();
    }

    public function getType(): string
    {
        return $this->profileEntity->getType();
    }

    public function getName(): string
    {
        return $this->profileEntity->getName();
    }

    public function getConfigNames(): array
    {
        return $this->configNames;
    }

    public function getEntity(): ProfileEntity
    {
        return $this->profileEntity;
    }
}
