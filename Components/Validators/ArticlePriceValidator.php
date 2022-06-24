<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators;

class ArticlePriceValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => [
            'orderNumber',
            'priceGroup',
            'name',
            'additionalText',
            'supplierName',
        ],
        'float' => [
            'price',
            'purchasePrice',
            'pseudoPrice',
            'regulationPrice',
        ],
        'int' => [
            'from',
        ],
    ];

    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        'orderNumber',
    ];

    /**
     * @var array<string, array<string>>
     */
    protected array $snippetData = [
        'orderNumber' => [
            'adapters/ordernumber_required',
            'Order number is required.',
        ],
    ];
}
