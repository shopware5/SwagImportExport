<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

class OrderMainDataProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::MAIN_ORDER_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_order_main_data';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_order_main_data_description';
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
                    'id' => '537359399c8b7',
                    'name' => 'mainOrders',
                    'index' => 0,
                    'type' => '',
                    'children' => [
                        0 => [
                                'id' => '537359399c90d',
                                'name' => 'mainOrder',
                                'index' => 0,
                                'type' => 'iteration',
                                'adapter' => 'order',
                                'parentKey' => '',
                                'shopwareField' => '',
                                'children' => $this->getOrderMainDataFields(),
                            ],
                    ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getOrderMainDataFields()
    {
        return [
            0 => [
                'id' => '53eca77b49d6d',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'orderId',
                'shopwareField' => 'orderId',
            ],
            1 => [
                'id' => '5373865547d06',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'orderNumber',
                'shopwareField' => 'orderNumber',
            ],
            2 => [
                'id' => '53ecb9c7d60ss',
                'type' => 'leaf',
                'index' => 2,
                'name' => 'invoiceNumber',
                'shopwareField' => 'invoiceNumber',
            ],
            3 => [
                'id' => '53ecb6a059334',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'invoiceAmount',
                'shopwareField' => 'invoiceAmount',
            ],
            4 => [
                'id' => '53ecb6a74e399',
                'type' => 'leaf',
                'index' => 4,
                'name' => 'invoiceAmountNet',
                'shopwareField' => 'invoiceAmountNet',
            ],
            5 => [
                'id' => '53ecb6b4587ba',
                'type' => 'leaf',
                'index' => 5,
                'name' => 'invoiceShipping',
                'shopwareField' => 'invoiceShipping',
            ],
            6 => [
                'id' => '53ecb6be27e2e',
                'type' => 'leaf',
                'index' => 6,
                'name' => 'invoiceShippingNet',
                'shopwareField' => 'invoiceShippingNet',
            ],
            7 => [
                'id' => '53fddf437e58c',
                'type' => 'node',
                'index' => 7,
                'name' => 'taxRateSums',
                'shopwareField' => '',
                'children' => $this->getTaxFields(),
            ],
            8 => [
                'id' => '53ecb7dd88b2a',
                'type' => 'leaf',
                'index' => 9,
                'name' => 'net',
                'shopwareField' => 'net',
            ],
            9 => [
                'id' => '53ecb7f518b2a',
                'type' => 'leaf',
                'index' => 10,
                'name' => 'taxFree',
                'shopwareField' => 'taxFree',
            ],
            10 => [
                'id' => '53ecb9c7d602d',
                'type' => 'leaf',
                'index' => 11,
                'name' => 'paymentName',
                'shopwareField' => 'paymentName',
            ],
            11 => [
                'id' => '53ecb9c7d60aa',
                'type' => 'leaf',
                'index' => 12,
                'name' => 'paymentStatus',
                'shopwareField' => 'paymentState',
            ],
            12 => [
                'id' => '53ecb9c7d60bb',
                'type' => 'leaf',
                'index' => 13,
                'name' => 'orderStatus',
                'shopwareField' => 'orderState',
            ],
            13 => [
                'id' => '53ecb8c42923d',
                'type' => 'leaf',
                'index' => 14,
                'name' => 'currency',
                'shopwareField' => 'currency',
            ],
            14 => [
                'id' => '53ecb8c74168b',
                'type' => 'leaf',
                'index' => 15,
                'name' => 'currencyFactor',
                'shopwareField' => 'currencyFactor',
            ],
            15 => [
                'id' => '53ecb6ebaf4c5',
                'type' => 'leaf',
                'index' => 16,
                'name' => 'transactionId',
                'shopwareField' => 'transactionId',
            ],
            16 => [
                'id' => '53ecb8bd55dda',
                'type' => 'leaf',
                'index' => 17,
                'name' => 'trackingCode',
                'shopwareField' => 'trackingCode',
            ],
            17 => [
                'id' => '53ecb6db22a2e',
                'type' => 'leaf',
                'index' => 18,
                'name' => 'orderTime',
                'shopwareField' => 'orderTime',
            ],
            18 => [
                'id' => '53ecb9c7d602e',
                'type' => 'leaf',
                'index' => 19,
                'name' => 'email',
                'shopwareField' => 'email',
            ],
            19 => [
                'id' => '53ecb9c7d602a',
                'type' => 'leaf',
                'index' => 20,
                'name' => 'customerNumber',
                'shopwareField' => 'customerNumber',
            ],
            20 => [
                'id' => '53ecb9c7d60cc',
                'type' => 'leaf',
                'index' => 21,
                'name' => 'customerGroup',
                'shopwareField' => 'customerGroupName',
            ],
            21 => [
                'id' => '53ecb9c7d6s12',
                'type' => 'leaf',
                'index' => 22,
                'name' => 'billingSalutation',
                'shopwareField' => 'billingSalutation',
            ],
            22 => [
                'id' => '53ecb9c7d602b',
                'type' => 'leaf',
                'index' => 23,
                'name' => 'billingFirstName',
                'shopwareField' => 'billingFirstName',
            ],
            23 => [
                'id' => '53ecb9c7d602c',
                'type' => 'leaf',
                'index' => 24,
                'name' => 'billingLastName',
                'shopwareField' => 'billingLastName',
            ],
            24 => [
                'id' => '53ecb9cab1623',
                'type' => 'leaf',
                'index' => 25,
                'name' => 'billingCompany',
                'shopwareField' => 'billingCompany',
            ],
            25 => [
                'id' => '53ecb9cab162a',
                'type' => 'leaf',
                'index' => 26,
                'name' => 'billingDepartment',
                'shopwareField' => 'billingDepartment',
            ],
            26 => [
                'id' => '53ecb9cab162b',
                'type' => 'leaf',
                'index' => 27,
                'name' => 'billingStreet',
                'shopwareField' => 'billingStreet',
            ],
            27 => [
                'id' => '53ecb9cab162c',
                'type' => 'leaf',
                'index' => 28,
                'name' => 'billingZipCode',
                'shopwareField' => 'billingZipCode',
            ],
            28 => [
                'id' => '53ecb9cab162d',
                'type' => 'leaf',
                'index' => 29,
                'name' => 'billingCity',
                'shopwareField' => 'billingCity',
            ],
            29 => [
                'id' => '53ecb9cab162e',
                'type' => 'leaf',
                'index' => 30,
                'name' => 'billingPhone',
                'shopwareField' => 'billingPhone',
            ],
            30 => [
                'id' => '53ecb9cab16a3',
                'type' => 'leaf',
                'index' => 31,
                'name' => 'billingFax',
                'shopwareField' => 'billingFax',
            ],
            31 => [
                'id' => '53ecb9cab16d3',
                'type' => 'leaf',
                'index' => 32,
                'name' => 'billingAdditionalAddressLine1',
                'shopwareField' => 'billingAdditionalAddressLine1',
            ],
            32 => [
                'id' => '53ecb9cab16q2',
                'type' => 'leaf',
                'index' => 33,
                'name' => 'billingAdditionalAddressLine2',
                'shopwareField' => 'billingAdditionalAddressLine2',
            ],
            33 => [
                'id' => '52ecb9cab16q2',
                'type' => 'leaf',
                'index' => 34,
                'name' => 'billingState',
                'shopwareField' => 'billingState',
            ],
            34 => [
                'id' => '52ecb9cab16qd',
                'type' => 'leaf',
                'index' => 35,
                'name' => 'billingCountry',
                'shopwareField' => 'billingCountry',
            ],
            35 => [
                'id' => '53ecb9cjd602a',
                'type' => 'leaf',
                'index' => 36,
                'name' => 'shippingSalutation',
                'shopwareField' => 'shippingSalutation',
            ],
            36 => [
                'id' => '53ecb9cld6s12',
                'type' => 'leaf',
                'index' => 37,
                'name' => 'shippingFirstName',
                'shopwareField' => 'shippingFirstName',
            ],
            37 => [
                'id' => '53ecb9cmd602b',
                'type' => 'leaf',
                'index' => 38,
                'name' => 'shippingLastName',
                'shopwareField' => 'shippingLastName',
            ],
            38 => [
                'id' => '53ecb9ctd602c',
                'type' => 'leaf',
                'index' => 39,
                'name' => 'shippingCompany',
                'shopwareField' => 'shippingCompany',
            ],
            39 => [
                'id' => '53ecb9ceb1623',
                'type' => 'leaf',
                'index' => 40,
                'name' => 'shippingDepartment',
                'shopwareField' => 'shippingDepartment',
            ],
            40 => [
                'id' => '53ecb9cyb162a',
                'type' => 'leaf',
                'index' => 41,
                'name' => 'shippingStreet',
                'shopwareField' => 'shippingStreet',
            ],
            41 => [
                'id' => '53ecb9ck2162b',
                'type' => 'leaf',
                'index' => 42,
                'name' => 'shippingZipCode',
                'shopwareField' => 'shippingZipCode',
            ],
            42 => [
                'id' => '53ecb9ca5162c',
                'type' => 'leaf',
                'index' => 43,
                'name' => 'shippingCity',
                'shopwareField' => 'shippingCity',
            ],
            43 => [
                'id' => '53ecb9caw16d3',
                'type' => 'leaf',
                'index' => 44,
                'name' => 'shippingAdditionalAddressLine1',
                'shopwareField' => 'shippingAdditionalAddressLine1',
            ],
            44 => [
                'id' => '53ecb9ca616q2',
                'type' => 'leaf',
                'index' => 45,
                'name' => 'shippingAdditionalAddressLine1',
                'shopwareField' => 'shippingAdditionalAddressLine1',
            ],
            45 => [
                'id' => '52eax9cab16q2',
                'type' => 'leaf',
                'index' => 46,
                'name' => 'shippingState',
                'shopwareField' => 'shippingState',
            ],
            46 => [
                'id' => '53ecb9c7d6020',
                'type' => 'leaf',
                'index' => 47,
                'name' => 'shippingCountry',
                'shopwareField' => 'shippingCountry',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getTaxFields()
    {
        return [
            0 => [
                'id' => '63e0d494n0b1d',
                'name' => 'taxRateSum',
                'index' => 0,
                'type' => 'iteration',
                'adapter' => 'taxRateSum',
                'parentKey' => 'orderId',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '83eab6be27a1a',
                        'type' => 'leaf',
                        'index' => 0,
                        'name' => 'taxRateSums',
                        'shopwareField' => 'taxRateSums',
                    ],
                    1 => [
                        'id' => '83eah9bi27a1a',
                        'type' => 'leaf',
                        'index' => 1,
                        'name' => 'taxRate',
                        'shopwareField' => 'taxRate',
                    ],
                ],
            ],
        ];
    }
}
