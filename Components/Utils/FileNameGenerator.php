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
        return sprintf(
            '%s.%s.%s-%s.%s',
            $operation,
            $profile->getType(),
            (new \DateTime('now'))->format('Y.m.d.h.i.s'),
            \substr(\md5(\uniqid()), 0, 8),
            $format
        );
    }
}
