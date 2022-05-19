<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Service;

interface UnderscoreToCamelCaseServiceInterface
{
    /**
     * @param string $string
     *
     * @return string
     */
    public function underscoreToCamelCase($string);
}
