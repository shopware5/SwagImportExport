<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

use Shopware\Bundle\CustomerSearchBundle\Condition\HasNoAddressWithCountryCondition;

class SwagVersionHelper
{
    public static function hasMinimumVersion(string $version): bool
    {
        $actualVersion = Shopware()->Config()->get('version');

        if ($actualVersion === '___VERSION___') {
            return true;
        }

        return \version_compare($actualVersion, $version, '>=');
    }

    public static function isShopware578(): bool
    {
        return class_exists(HasNoAddressWithCountryCondition::class);
    }
}
