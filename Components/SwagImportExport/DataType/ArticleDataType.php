<?php

namespace Shopware\Components\SwagImportExport\DataType;

class ArticleDataType
{
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

    public static $defaultFieldsForCreate = array(
        'id' => array(
            'supplierId'
        ),
        'date' => array(
            'availableFrom',
            'availableTo',
            'shippingTime',
        ),
        'integer' => array(
            'inStock',
            'tax',
        ),
        'string' => array(
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
        'boolean' => array(
            'active',
            'shippingFree',
        )
    );
}