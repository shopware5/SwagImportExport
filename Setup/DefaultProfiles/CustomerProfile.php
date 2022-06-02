<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class CustomerProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::CUSTOMER_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_customers';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_customers_description';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' => [
                0 => [
                    'id' => '537359399c80a',
                    'name' => 'Header',
                    'index' => 0,
                    'type' => 'node',
                    'children' => [
                        0 => [
                            'id' => '537385ed7c799',
                            'name' => 'HeaderChild',
                            'index' => 0,
                            'type' => 'node',
                            'shopwareField' => '',
                        ],
                    ],
                ],
                1 => [
                    'id' => '537359399c8b7',
                    'name' => 'customers',
                    'index' => 1,
                    'type' => '',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '53ea047e7dca5',
                            'name' => 'customer',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getCustomerFields(),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getCustomerFields(): array
    {
        return [
            0 => [
                'id' => '53ea048def53f',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'customernumber',
                'shopwareField' => 'customerNumber',
            ],
            1 => [
                'id' => '53ea052c8f4c9',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'email',
                'shopwareField' => 'email',
            ],
            2 => [
                'id' => '53ea0535e3348',
                'type' => 'leaf',
                'index' => 2,
                'name' => 'password',
                'shopwareField' => 'password',
            ],
            3 => [
                'id' => '53fb366466188',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'encoder',
                'shopwareField' => 'encoder',
            ],
            4 => [
                'id' => '540d9e8c6ab4f',
                'type' => 'leaf',
                'index' => 4,
                'name' => 'active',
                'shopwareField' => 'active',
            ],
            5 => [
                'id' => '53ea054339f8e',
                'type' => 'leaf',
                'index' => 5,
                'name' => 'billing_company',
                'shopwareField' => 'billingCompany',
            ],
            6 => [
                'id' => '53ea057725a7d',
                'type' => 'leaf',
                'index' => 6,
                'name' => 'billing_department',
                'shopwareField' => 'billingDepartment',
            ],
            7 => [
                'id' => '53ea0595b1d31',
                'type' => 'leaf',
                'index' => 7,
                'name' => 'billing_salutation',
                'shopwareField' => 'billingSalutation',
            ],
            8 => [
                'id' => '53ea05dba6a4d',
                'type' => 'leaf',
                'index' => 8,
                'name' => 'billing_firstname',
                'shopwareField' => 'billingFirstname',
            ],
            9 => [
                'id' => '53ea05de1204b',
                'type' => 'leaf',
                'index' => 9,
                'name' => 'billing_lastname',
                'shopwareField' => 'billingLastname',
            ],
            10 => [
                'id' => '53ea05df9caf1',
                'type' => 'leaf',
                'index' => 10,
                'name' => 'billing_street',
                'shopwareField' => 'billingStreet',
            ],
            11 => [
                'id' => '53ea05e271edd',
                'type' => 'leaf',
                'index' => 12,
                'name' => 'billing_zipcode',
                'shopwareField' => 'billingZipcode',
            ],
            12 => [
                'id' => '53ea05e417656',
                'type' => 'leaf',
                'index' => 13,
                'name' => 'billing_city',
                'shopwareField' => 'billingCity',
            ],
            13 => [
                'id' => '53ea05e5e2e12',
                'type' => 'leaf',
                'index' => 14,
                'name' => 'phone',
                'shopwareField' => 'billingPhone',
            ],
            14 => [
                'id' => '53ea065093393',
                'type' => 'leaf',
                'index' => 15,
                'name' => 'fax',
                'shopwareField' => 'billingFax',
            ],
            15 => [
                'id' => '53ea0652597f1',
                'type' => 'leaf',
                'index' => 16,
                'name' => 'billing_countryID',
                'shopwareField' => 'billingCountryID',
            ],
            16 => [
                'id' => '53ea0653ddf4a',
                'type' => 'leaf',
                'index' => 17,
                'name' => 'billing_stateID',
                'shopwareField' => 'billingStateID',
            ],
            17 => [
                'id' => '53ea0691b1774',
                'type' => 'leaf',
                'index' => 18,
                'name' => 'ustid',
                'shopwareField' => 'ustid',
            ],
            18 => [
                'id' => '53ea069d37da6',
                'type' => 'leaf',
                'index' => 19,
                'name' => 'shipping_company',
                'shopwareField' => 'shippingCompany',
            ],
            19 => [
                'id' => '53ea069eac2c6',
                'type' => 'leaf',
                'index' => 20,
                'name' => 'shipping_department',
                'shopwareField' => 'shippingDepartment',
            ],
            20 => [
                'id' => '53ea06a0013c7',
                'type' => 'leaf',
                'index' => 21,
                'name' => 'shipping_salutation',
                'shopwareField' => 'shippingSalutation',
            ],
            21 => [
                'id' => '53ea06a23cdc1',
                'type' => 'leaf',
                'index' => 22,
                'name' => 'shipping_firstname',
                'shopwareField' => 'shippingFirstname',
            ],
            22 => [
                'id' => '53ea0e4a3792d',
                'type' => 'leaf',
                'index' => 23,
                'name' => 'shipping_lastname',
                'shopwareField' => 'shippingLastname',
            ],
            23 => [
                'id' => '53ea0e4fda6e7',
                'type' => 'leaf',
                'index' => 24,
                'name' => 'shipping_street',
                'shopwareField' => 'shippingStreet',
            ],
            24 => [
                'id' => '53ea0e55b2b31',
                'type' => 'leaf',
                'index' => 26,
                'name' => 'shipping_zipcode',
                'shopwareField' => 'shippingZipcode',
            ],
            25 => [
                'id' => '53ea0e57ddba7',
                'type' => 'leaf',
                'index' => 27,
                'name' => 'shipping_city',
                'shopwareField' => 'shippingCity',
            ],
            26 => [
                'id' => '53ea0e5a4ee0c',
                'type' => 'leaf',
                'index' => 28,
                'name' => 'shipping_countryID',
                'shopwareField' => 'shippingCountryID',
            ],
            27 => [
                'id' => '53ea0e5c6d67e',
                'type' => 'leaf',
                'index' => 29,
                'name' => 'paymentID',
                'shopwareField' => 'paymentID',
            ],
            28 => [
                'id' => '53ea0e5e88347',
                'type' => 'leaf',
                'index' => 30,
                'name' => 'newsletter',
                'shopwareField' => 'newsletter',
            ],
            29 => [
                'id' => '53ea0e6194ba6',
                'type' => 'leaf',
                'index' => 31,
                'name' => 'accountmode',
                'shopwareField' => 'accountMode',
            ],
            30 => [
                'id' => '53ea118664a90',
                'type' => 'leaf',
                'index' => 32,
                'name' => 'customergroup',
                'shopwareField' => 'customergroup',
            ],
            31 => [
                'id' => '53ea1188ca4ca',
                'type' => 'leaf',
                'index' => 33,
                'name' => 'language',
                'shopwareField' => 'language',
            ],
            32 => [
                'id' => '53ea118b67fe2',
                'type' => 'leaf',
                'index' => 34,
                'name' => 'subshopID',
                'shopwareField' => 'subshopID',
            ],
        ];
    }
}
