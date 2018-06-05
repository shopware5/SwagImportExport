<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DataType;

class ArticleDataType
{
    /**
     * @var array
     */
    public static $mapper = [
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
     * @var array
     */
    public static $defaultFieldsForCreate = [
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
     * @var array
     */
    public static $defaultFieldsValues = [
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
     * @var array
     */
    public static $articleFieldsMapping = [
        'added' => 'date',
        'changed' => 'changeTime',
        'highlight' => 'topSeller',
    ];

    /**
     * @var array
     */
    public static $articleVariantFieldsMapping = [
        'number' => 'orderNumber',
        'len' => 'length',
    ];
}
