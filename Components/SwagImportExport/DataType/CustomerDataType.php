<?php

namespace Shopware\Components\SwagImportExport\DataType;

class CustomerDataType
{
    /**
     * @var array
     */
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

    /**
     * @var array
     */
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

    /**
     * @var array
     */
    public static $defaultFieldsValues = array(
        'string' => array(
            'attrBillingText1',
            'attrBillingText2',
            'attrBillingText3',
            'attrBillingText4',
            'attrBillingText5',
            'attrBillingText6',
            'attrShippingText1',
            'attrShippingText2',
            'attrShippingText3',
            'attrShippingText4',
            'attrShippingText5',
            'attrShippingText6',
        )
    );
}
