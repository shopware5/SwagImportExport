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
            'shippingZipcode',
            'customergroup',
            'language'
        ),
        'email' => array(
            'email'
        ),
        'int' => array(
            'active',
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