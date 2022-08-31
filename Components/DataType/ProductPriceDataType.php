<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataType;

class ProductPriceDataType
{
    public static array $defaultFieldsValues = [
        'float' => [
            'percent',
        ],
    ];

    private function __construct()
    {
    }
}
