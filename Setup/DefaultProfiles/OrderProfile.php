<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class OrderProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::ORDER_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_orders';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_orders_description';
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
                    'name' => 'orders',
                    'index' => 1,
                    'type' => 'node',
                    'children' => [
                        0 => [
                            'id' => '537359399c90d',
                            'name' => 'order',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'attributes' => null,
                            'children' => $this->getOrderFields(),
                            'shopwareField' => '',
                            'parentKey' => '',
                        ],
                    ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }

    private function getOrderFields(): array
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
                    'name' => 'number',
                    'index' => 1,
                    'type' => 'leaf',
                    'shopwareField' => 'number',
                ],
            2 => [
                    'id' => '53ecb1fa09cfd',
                    'type' => 'leaf',
                    'index' => 2,
                    'name' => 'customerId',
                    'shopwareField' => 'customerId',
                ],
            3 => [
                    'id' => '53ecb3a3e43fb',
                    'type' => 'leaf',
                    'index' => 3,
                    'name' => 'orderStatusID',
                    'shopwareField' => 'status',
                ],
            4 => [
                    'id' => '53ecb496e80e0',
                    'type' => 'leaf',
                    'index' => 4,
                    'name' => 'cleared',
                    'shopwareField' => 'cleared',
                ],
            5 => [
                    'id' => '53ecb4e584159',
                    'type' => 'leaf',
                    'index' => 5,
                    'name' => 'paymentID',
                    'shopwareField' => 'paymentId',
                ],
            6 => [
                    'id' => '53ecb4f9a203b',
                    'type' => 'leaf',
                    'index' => 6,
                    'name' => 'dispatchId',
                    'shopwareField' => 'dispatchId',
                ],
            7 => [
                    'id' => '53ecb510a3379',
                    'type' => 'leaf',
                    'index' => 7,
                    'name' => 'partnerId',
                    'shopwareField' => 'partnerId',
                ],
            8 => [
                    'id' => '53ecb51a93f21',
                    'type' => 'leaf',
                    'index' => 8,
                    'name' => 'shopId',
                    'shopwareField' => 'shopId',
                ],
            9 => [
                    'id' => '53ecb6a059334',
                    'type' => 'leaf',
                    'index' => 9,
                    'name' => 'invoiceAmount',
                    'shopwareField' => 'invoiceAmount',
                ],
            10 => [
                    'id' => '53ecb6a74e399',
                    'type' => 'leaf',
                    'index' => 10,
                    'name' => 'invoiceAmountNet',
                    'shopwareField' => 'invoiceAmountNet',
                ],
            11 => [
                    'id' => '53ecb6b4587ba',
                    'type' => 'leaf',
                    'index' => 11,
                    'name' => 'invoiceShipping',
                    'shopwareField' => 'invoiceShipping',
                ],
            12 => [
                    'id' => '53ecb6be27e2e',
                    'type' => 'leaf',
                    'index' => 12,
                    'name' => 'invoiceShippingNet',
                    'shopwareField' => 'invoiceShippingNet',
                ],
            13 => [
                    'id' => '53ecb6db22a2e',
                    'type' => 'leaf',
                    'index' => 13,
                    'name' => 'orderTime',
                    'shopwareField' => 'orderTime',
                ],
            14 => [
                    'id' => '53ecb6ebaf4c5',
                    'type' => 'leaf',
                    'index' => 14,
                    'name' => 'transactionId',
                    'shopwareField' => 'transactionId',
                ],
            15 => [
                    'id' => '53ecb7014e7ad',
                    'type' => 'leaf',
                    'index' => 15,
                    'name' => 'comment',
                    'shopwareField' => 'comment',
                ],
            16 => [
                    'id' => '53ecb7f0df5db',
                    'type' => 'leaf',
                    'index' => 16,
                    'name' => 'customerComment',
                    'shopwareField' => 'customerComment',
                ],
            17 => [
                    'id' => '53ecb7f265873',
                    'type' => 'leaf',
                    'index' => 17,
                    'name' => 'internalComment',
                    'shopwareField' => 'internalComment',
                ],
            18 => [
                    'id' => '53ecb7f3baed3',
                    'type' => 'leaf',
                    'index' => 18,
                    'name' => 'net',
                    'shopwareField' => 'net',
                ],
            19 => [
                    'id' => '53ecb7f518b2a',
                    'type' => 'leaf',
                    'index' => 19,
                    'name' => 'taxFree',
                    'shopwareField' => 'taxFree',
                ],
            20 => [
                    'id' => '53ecb7f778bb0',
                    'type' => 'leaf',
                    'index' => 20,
                    'name' => 'temporaryId',
                    'shopwareField' => 'temporaryId',
                ],
            21 => [
                    'id' => '53ecb7f995899',
                    'type' => 'leaf',
                    'index' => 21,
                    'name' => 'referer',
                    'shopwareField' => 'referer',
                ],
            22 => [
                    'id' => '53ecb8ba28544',
                    'type' => 'leaf',
                    'index' => 22,
                    'name' => 'clearedDate',
                    'shopwareField' => 'clearedDate',
                ],
            23 => [
                    'id' => '53ecb8bd55dda',
                    'type' => 'leaf',
                    'index' => 23,
                    'name' => 'trackingCode',
                    'shopwareField' => 'trackingCode',
                ],
            24 => [
                    'id' => '53ecb8c076318',
                    'type' => 'leaf',
                    'index' => 24,
                    'name' => 'languageIso',
                    'shopwareField' => 'languageIso',
                ],
            25 => [
                    'id' => '53ecb8c42923d',
                    'type' => 'leaf',
                    'index' => 25,
                    'name' => 'currency',
                    'shopwareField' => 'currency',
                ],
            26 => [
                    'id' => '53ecb8c74168b',
                    'type' => 'leaf',
                    'index' => 26,
                    'name' => 'currencyFactor',
                    'shopwareField' => 'currencyFactor',
                ],
            27 => [
                    'id' => '53ecb9203cb33',
                    'type' => 'leaf',
                    'index' => 27,
                    'name' => 'remoteAddress',
                    'shopwareField' => 'remoteAddress',
                ],
            28 => [
                    'id' => '53fddf437e561',
                    'type' => 'node',
                    'index' => 28,
                    'name' => 'details',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                                'id' => '53ecb9c7d602d',
                                'type' => 'leaf',
                                'index' => 0,
                                'name' => 'orderDetailId',
                                'shopwareField' => 'orderDetailId',
                            ],
                        1 => [
                                'id' => '53ecb9ee6f821',
                                'type' => 'leaf',
                                'index' => 1,
                                'name' => 'articleId',
                                'shopwareField' => 'articleId',
                            ],
                        2 => [
                                'id' => '53ecbaa627334',
                                'type' => 'leaf',
                                'index' => 2,
                                'name' => 'taxId',
                                'shopwareField' => 'taxId',
                            ],
                        3 => [
                                'id' => '53ecba416356a',
                                'type' => 'leaf',
                                'index' => 3,
                                'name' => 'taxRate',
                                'shopwareField' => 'taxRate',
                            ],
                        4 => [
                                'id' => '53ecbaa813093',
                                'type' => 'leaf',
                                'index' => 4,
                                'name' => 'statusId',
                                'shopwareField' => 'statusId',
                            ],
                        5 => [
                                'id' => '53ecbb05eccf1',
                                'type' => 'leaf',
                                'index' => 5,
                                'name' => 'number',
                                'shopwareField' => 'number',
                            ],
                        6 => [
                                'id' => '53ecbb0411d43',
                                'type' => 'leaf',
                                'index' => 6,
                                'name' => 'articleNumber',
                                'shopwareField' => 'articleNumber',
                            ],
                        7 => [
                                'id' => '53ecba19dc9ef',
                                'type' => 'leaf',
                                'index' => 7,
                                'name' => 'price',
                                'shopwareField' => 'price',
                            ],
                        8 => [
                                'id' => '53ecba29e1a37',
                                'type' => 'leaf',
                                'index' => 8,
                                'name' => 'quantity',
                                'shopwareField' => 'quantity',
                            ],
                        9 => [
                                'id' => '53ecba34bf110',
                                'type' => 'leaf',
                                'index' => 9,
                                'name' => 'articleName',
                                'shopwareField' => 'articleName',
                            ],
                        10 => [
                                'id' => '53ecbb07dda54',
                                'type' => 'leaf',
                                'index' => 10,
                                'name' => 'shipped',
                                'shopwareField' => 'shipped',
                            ],
                        11 => [
                                'id' => '53ecbb09bb007',
                                'type' => 'leaf',
                                'index' => 11,
                                'name' => 'shippedGroup',
                                'shopwareField' => 'shippedGroup',
                            ],
                        12 => [
                                'id' => '53ecbbc15479a',
                                'type' => 'leaf',
                                'index' => 12,
                                'name' => 'releaseDate',
                                'shopwareField' => 'releasedate',
                            ],
                        13 => [
                                'id' => '53ecbbc40bcd3',
                                'type' => 'leaf',
                                'index' => 13,
                                'name' => 'mode',
                                'shopwareField' => 'mode',
                            ],
                        14 => [
                                'id' => '53ecbbc57169d',
                                'type' => 'leaf',
                                'index' => 14,
                                'name' => 'esdArticle',
                                'shopwareField' => 'esd',
                            ],
                        15 => [
                                'id' => '53ecbbc6b6f2c',
                                'type' => 'leaf',
                                'index' => 15,
                                'name' => 'config',
                                'shopwareField' => 'config',
                            ],
                    ],
                ],
        ];
    }
}
