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
    public static function isShopware578(): bool
    {
        return class_exists(HasNoAddressWithCountryCondition::class);
    }
}
