<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataType;

class CustomerDataType
{
    /**
     * @var array<string, array<string>>
     */
    public static array $mapper = [
        'string' => [
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
            'ustid',
            'shippingCompany',
            'shippingDepartment',
            'shippingSalutation',
            'shippingFirstname',
            'shippingLastname',
            'shippingStreet',
            'shippingZipcode',
            'customergroup',
            'language',
        ],
        'email' => [
            'email',
        ],
        'int' => [
            'active',
            'billingCountryID',
            'billingStateID',
            'shippingCountryID',
            'paymentID',
            'newsletter',
            'accountMode',
            'subshopID',
        ],
    ];

    /**
     * @var array<string, array<string>>
     */
    public static array $defaultFieldsForCreate = [
        'boolean' => [
            'active',
        ],
        'id' => [
            'subshopID',
            'paymentID',
        ],
        'string' => [
            'encoder',
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
        ],
    ];

    /**
     * @var array<string, array<string>>
     */
    public static array $defaultFieldsValues = [
        'string' => [
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
        ],
    ];

    private function __construct()
    {
    }
}
