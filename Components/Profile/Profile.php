<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Profile;

use SwagImportExport\Models\Profile as ProfileEntity;

class Profile
{
    private ProfileEntity $profileEntity;

    public function __construct(ProfileEntity $profile)
    {
        $this->profileEntity = $profile;
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

    public function getEntity(): ProfileEntity
    {
        return $this->profileEntity;
    }
}
