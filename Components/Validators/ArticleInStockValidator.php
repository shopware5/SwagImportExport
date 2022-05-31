<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators;

class ArticleInStockValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => [
            'orderNumber',
            'additionalText',
            'supplier',
        ],
        'int' => ['inStock'],
        'float' => ['price'],
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
            'Order number is required',
        ],
    ];
}
