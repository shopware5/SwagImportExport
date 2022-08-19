<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

use SwagImportExport\Models\Profile;

class FileNameGenerator
{
    public static function generateFileName(string $operation, string $format, Profile $profile): string
    {
        $fileFormat = $format;

        $adapterType = $profile->getType();

        $hash = \substr(\md5(\uniqid()), 0, 8);

        $dateTime = new \DateTime('now');

        return sprintf('%s.%s.%s-%s.%s', $operation, $adapterType, $dateTime->format('Y.m.d.h.i.s'), $hash, $fileFormat);
    }
}
