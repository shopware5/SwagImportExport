<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

class UnderscoreToCamelCaseService implements UnderscoreToCamelCaseServiceInterface
{
    public function underscoreToCamelCase(?string $string): ?string
    {
        if (!\is_string($string)) {
            return '';
        }

        $func = function ($c) {
            return \strtoupper($c[1]);
        };

        return \lcfirst(\preg_replace_callback('/_([a-zA-Z])/', $func, $string));
    }
}
