<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

use Shopware\Components\SwagImportExport\Service\Struct\ProfileDataStruct;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface ProfileServiceInterface
 */
interface ProfileServiceInterface
{
    /**
     * @param UploadedFile $file
     *
     * @throws \Exception
     */
    public function importProfile(UploadedFile $file);

    /**
     * @param int $profileId
     *
     * @return ProfileDataStruct
     */
    public function exportProfile($profileId);
}
