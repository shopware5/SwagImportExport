<?php

namespace Shopware\Components\SwagImportExport\DataType;

class ArticleDataType
{
    /**
     * @var array
     */
    public static $mapper = array(
        'string' => array(
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
        ),
        'float' => array(
            'tax',
            'purchaseUnit',
            'referenceUnit',
            'weight',
            'width',
            'height',
            'length',
        ),
        'int' => array(
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
        ),
        'dateTime' => array(
            'date',
            'changeTime',
            'releaseDate',
        ),
    );

    /**
     * @var array
     */
    public static $defaultFieldsForCreate = array(
        'date' => array(
            'availableFrom',
            'availableTo',
            'added'
        ),
        'integer' => array(
            'inStock',
            'tax',
            'stockMin'
        ),
        'float' => array(
            'weight',
        ),
        'string' => array(
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
            'attributeAttr20'
        ),
        'id' => array(
            'supplierId'
        ),
        'boolean' => array(
            'active',
            'shippingFree',
        )
    );

    /**
     * @var array
     */
    public static $defaultFieldsValues = array (
        'string' => array(
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
            'attributeAttr20'
        ),
        'date' => array(
            'added'
        ),
        'int' => array(
            'inStock',
            'stockMin',
        ),
        'float' => array(
            'weight'
        )
    );

    /**
     * @var array
     */
    public static $articleFieldsMapping = array(
        'added' => 'date',
        'changed' => 'changeTime',
        'highlight' => 'topSeller'
    );

    /**
     * @var array
     */
    public static $articleVariantFieldsMapping = array(
        'number' => 'orderNumber',
        'len' => 'length',
    );
}
