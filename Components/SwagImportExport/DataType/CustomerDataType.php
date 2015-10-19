<?php

namespace Shopware\Components\SwagImportExport\DataType;

class CustomerDataType
{
    public static $mapper = array(
        'string' => array(
            'customerNumber',
            'password',
            'encoder',
            'billingCompany',
            'billingDepartment',
            'billingSalutation',
            'billingFirstname',
            'billingLastname',
            'billingStreet',
            'billingStreetnumber',
            'billingZipcode',
            'billingCity',
            'billingPhone',
            'billingFax',
            'ustid',
            'shippingCompany',
            'shippingDepartment',
            'shippingSalutation',
            'shippingFirstname',
            'shippingLastname',
            'shippingStreet',
            'shippingStreetnumber',
            'shippingZipcode',
            'customergroup',
            'language',
            'active',
        ),
        'email' => array(
            'email'
        ),
        'int' => array(
            'billingCountryID',
            'billingStateID',
            'shippingCountryID',
            'paymentID',
            'newsletter',
            'accountMode',
            'subshopID',
        ),
    );

    public static $defaultFieldsForCreate = array(
        'boolean' => array(
            'active',
        ),
        'id' => array(
            'subshopID',
            'paymentID',
        ),
        'string' => array(
            'encoder'
        )
    );
}