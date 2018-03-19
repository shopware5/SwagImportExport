<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

class CustomerCompleteProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::CUSTOMER_COMPLETE_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_customers_complete';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_customers_complete_description';
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
                            'shopwareField' => ''
                        ]
                    ]
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
                            'adapter' => 'customers',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getCustomerFields()
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getCustomerFields()
    {
        return [
            0 => [
                'id' => '53ea048def53f',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'customerNumber',
                'shopwareField' => 'number'
            ],
            1 => [
                'id' => '53ea052c8f4c9',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'email',
                'shopwareField' => 'email'
            ],
            2 => [
                'id' => '53ea0535e3348',
                'type' => 'leaf',
                'index' => 2,
                'name' => 'password',
                'shopwareField' => 'hashPassword'
            ],
            3 => [
                'id' => '53fb366466188',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'encoder',
                'shopwareField' => 'encoderName'
            ],
            4 => [
                'id' => '540d9e8c6ab4f',
                'type' => 'leaf',
                'index' => 4,
                'name' => 'active',
                'shopwareField' => 'active'
            ],
            5 => [
                'id' => '53ea054339f8e',
                'type' => 'leaf',
                'index' => 5,
                'name' => 'accountMode',
                'shopwareField' => 'accountMode'
            ],
            6 => [
                'id' => '53ea057725a7d',
                'type' => 'leaf',
                'index' => 6,
                'name' => 'confirmationKey',
                'shopwareField' => 'confirmationKey'
            ],
            7 => [
                'id' => '53ea0595b1d31',
                'type' => 'leaf',
                'index' => 7,
                'name' => 'paymentId',
                'shopwareField' => 'paymentId'
            ],
            8 => [
                'id' => '53ea05dba6a4d',
                'type' => 'leaf',
                'index' => 8,
                'name' => 'firstLogin',
                'shopwareField' => 'firstLogin'
            ],
            9 => [
                'id' => '53ea05de1204b',
                'type' => 'leaf',
                'index' => 9,
                'name' => 'lastLogin',
                'shopwareField' => 'lastLogin'
            ],
            10 => [
                'id' => '53ea05df9caf1',
                'type' => 'leaf',
                'index' => 10,
                'name' => 'sessionId',
                'shopwareField' => 'sessionId'
            ],
            11 => [
                'id' => '53ea05e271edd',
                'type' => 'leaf',
                'index' => 11,
                'name' => 'newsletter',
                'shopwareField' => 'newsletter'
            ],
            12 => [
                'id' => '53ea05e417656',
                'type' => 'leaf',
                'index' => 12,
                'name' => 'validation',
                'shopwareField' => 'validation'
            ],
            13 => [
                'id' => '53ea05e5e2e12',
                'type' => 'leaf',
                'index' => 13,
                'name' => 'affiliate',
                'shopwareField' => 'affiliate'
            ],
            14 => [
                'id' => '53ea065093393',
                'type' => 'leaf',
                'index' => 14,
                'name' => 'customerGroupKey',
                'shopwareField' => 'groupKey'
            ],
            15 => [
                'id' => '53ea0652597f1',
                'type' => 'leaf',
                'index' => 15,
                'name' => 'paymentPresetId',
                'shopwareField' => 'paymentPreset'
            ],
            16 => [
                'id' => '53ea0653ddf4a',
                'type' => 'leaf',
                'index' => 16,
                'name' => 'languageId',
                'shopwareField' => 'languageId'
            ],
            17 => [
                'id' => '53ea0691b1774',
                'type' => 'leaf',
                'index' => 17,
                'name' => 'referer',
                'shopwareField' => 'referer'
            ],
            18 => [
                'id' => '53ea069d37da6',
                'type' => 'leaf',
                'index' => 18,
                'name' => 'failedLogins',
                'shopwareField' => 'failedLogins'
            ],
            19 => [
                'id' => '53ea069eac2c6',
                'type' => 'leaf',
                'index' => 19,
                'name' => 'lockedUntil',
                'shopwareField' => 'lockedUntil'
            ],
            20 => [
                'id' => '53ea06a0013c7',
                'type' => 'leaf',
                'index' => 20,
                'name' => 'title',
                'shopwareField' => 'title'
            ],
            21 => [
                'id' => '53ea06a23cdc1',
                'type' => 'leaf',
                'index' => 21,
                'name' => 'firstname',
                'shopwareField' => 'firstname'
            ],
            22 => [
                'id' => '53ea0e4a3792d',
                'type' => 'leaf',
                'index' => 22,
                'name' => 'lastname',
                'shopwareField' => 'lastname'
            ],
            23 => [
                'id' => '53ea0e4fda6e7',
                'type' => 'leaf',
                'index' => 23,
                'name' => 'birthday',
                'shopwareField' => 'birthday'
            ],
            24 => [
                'id' => '53ea0e55b2b31',
                'type' => 'leaf',
                'index' => 24,
                'name' => 'priceGroupId',
                'shopwareField' => 'priceGroupId'
            ],
            25 => [
                'id' => '55dc3386b8c5f',
                'name' => 'addresses',
                'index' => 25,
                'type' => 'node',
                'children' => [
                    0 => [
                        'id' => '55dc33e8040e5',
                        'name' => 'address',
                        'index' => 0,
                        'type' => 'raw',
                        'rawKey' => 'addresses'
                    ]
                ]
            ],
            26 => [
                'id' => '537359399c8b8',
                'name' => 'orders',
                'index' => 26,
                'type' => 'node',
                'children' => [
                    0 => [
                        'id' => '55dc33e8040e5',
                        'name' => 'order',
                        'index' => 0,
                        'type' => 'raw',
                        'rawKey' => 'orders',
                        'children' => [
                            0 => [
                                'id' => '542a5df925af2',
                                'name' => 'details',
                                'index' => 26,
                                'type' => 'node',
                                'children' => [
                                    0 => [
                                        'id' => '55d59f9798545',
                                        'name' => 'detail',
                                        'index' => 0,
                                        'type' => 'raw',
                                        'rawKey' => 'details'
                                    ]
                                ],
                                'shopwareField' => ''
                            ]
                        ]
                    ]
                ],
                'shopwareField' => ''
            ]
        ];
    }
}
