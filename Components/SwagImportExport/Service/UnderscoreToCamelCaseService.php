<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Service;

class UnderscoreToCamelCaseService implements UnderscoreToCamelCaseServiceInterface
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function underscoreToCamelCase($string)
    {
        $func = function ($c) {
            return strtoupper($c[1]);
        };

        return lcfirst(preg_replace_callback('/_([a-zA-Z])/', $func, $string));
    }
}
