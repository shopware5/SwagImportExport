<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Validators;

class ProductImageValidator extends Validator
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => [
            'ordernumber',
            'image',
            'description',
            'relations',
        ],
        'int' => [
            'main',
            'position',
            'width',
            'height',
        ],
    ];

    /**
     * @var array<string>
     */
    protected array $requiredFields = [
        'ordernumber',
        'image',
    ];

    /**
     * @var array<string, array<string>>
     */
    protected array $snippetData = [
        'ordernumber' => [
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required',
        ],
        'image' => [
            'adapters/articlesImages/ordernumber_image_required',
            'Ordernumber and image are required',
        ],
    ];
}
