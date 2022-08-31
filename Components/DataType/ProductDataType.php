<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataType;

class ProductDataType
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => [
            'orderNumber',
            'mainNumber',
            'name',
            'additionalText',
            'supplierName',
            'description',
            'descriptionLong',
            'shippingTime',
            'metaTitle',
            'keywords',
            'packUnit',
            'supplierNumber',
            'ean',
        ],
        'float' => [
            'tax',
            'purchaseUnit',
            'referenceUnit',
            'weight',
            'width',
            'height',
            'length',
        ],
        'int' => [
            'active',
            'inStock',
            'stockMin',
            'shippingFree',
            'topSeller',
            'minPurchase',
            'purchaseSteps',
            'maxPurchase',
            'unitId',
            'priceGroupId',
            'priceGroupActive',
            'lastStock',
        ],
        'dateTime' => [
            'date',
            'changeTime',
            'releaseDate',
        ],
    ];

    /**
     * @var array<string, array<string>>
     */
    public static array $defaultFieldsForCreate = [
        'date' => [
            'availableFrom',
            'availableTo',
            'added',
        ],
        'integer' => [
            'inStock',
            'tax',
            'stockMin',
        ],
        'float' => [
            'weight',
        ],
        'string' => [
            'shippingTime',
            'supplierName',
            'attributeAttr1',
            'attributeAttr2',
            'attributeAttr3',
            'attributeAttr4',
            'attributeAttr5',
            'attributeAttr6',
            'attributeAttr7',
            'attributeAttr8',
            'attributeAttr9',
            'attributeAttr10',
            'attributeAttr11',
            'attributeAttr12',
            'attributeAttr13',
            'attributeAttr14',
            'attributeAttr15',
            'attributeAttr16',
            'attributeAttr17',
            'attributeAttr18',
            'attributeAttr19',
            'attributeAttr20',
        ],
        'id' => [
            'supplierId',
        ],
        'boolean' => [
            'active',
            'shippingFree',
        ],
    ];

    /**
     * @var array<string, array<string>>
     */
    public static array $defaultFieldsValues = [
        'string' => [
            'description',
            'descriptionLong',
            'metaTitle',
            'keywords',
            'supplierNumber',
            'additionalText',
            'ean',
            'packUnit',
            'attributeAttr1',
            'attributeAttr2',
            'attributeAttr3',
            'attributeAttr4',
            'attributeAttr5',
            'attributeAttr6',
            'attributeAttr7',
            'attributeAttr8',
            'attributeAttr9',
            'attributeAttr10',
            'attributeAttr11',
            'attributeAttr12',
            'attributeAttr13',
            'attributeAttr14',
            'attributeAttr15',
            'attributeAttr16',
            'attributeAttr17',
            'attributeAttr18',
            'attributeAttr19',
            'attributeAttr20',
        ],
        'date' => [
            'added',
            'changed',
        ],
        'int' => [
            'inStock',
            'stockMin',
        ],
        'float' => [
            'weight',
            'purchasePrice',
        ],
    ];

    /**
     * @var array<string, string>
     */
    public static array $productFieldsMapping = [
        'added' => 'date',
        'changed' => 'changeTime',
        'highlight' => 'topSeller',
    ];

    /**
     * @var array<string, string>
     */
    public static array $productVariantFieldsMapping = [
        'number' => 'orderNumber',
        'len' => 'length',
    ];

    private function __construct()
    {
    }
}
