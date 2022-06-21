<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

use SwagImportExport\Components\Service\Struct\ProfileDataStruct;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ProfileServiceInterface
{
    /**
     * @throws \Exception
     */
    public function importProfile(UploadedFile $file): void;

    public function exportProfile(int $profileId): ProfileDataStruct;
}
