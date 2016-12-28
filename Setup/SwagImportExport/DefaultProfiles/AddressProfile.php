<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

class AddressProfile implements ProfileMetaData, \JsonSerializable
{
    /**
     * @inheritdoc
     */
    public function getAdapter()
    {
        return DataDbAdapter::ADDRESS_ADAPTER;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'default_addresses';
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' => [
                [
                    'id' => '58458e1c359ea',
                    'name' => 'addresses',
                    'index' => 0,
                    'type' => '',
                    'children' => [
                        [
                            'id' => '58458e1c21722',
                            'name' => 'address',
                            'type' => 'iteration',
                            'adapter' => 'address',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getAddressFields()
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getAddressFields()
    {
        return [
            [
                'id' => '58458e1c01c46',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'id',
                'shopwareField' => 'id'
            ],
            [
                'id' => '58458e1c01a47',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'userID',
                'shopwareField' => 'userID'
            ],
            [
                'id' => '5846753b011f1',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'email',
                'shopwareField' => 'email'
            ],
            [
                'id' => '5846753b01794',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'customernumber',
                'shopwareField' => 'customernumber'
            ],
            [
                'id' => '58458e1c00f88',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'company',
                'shopwareField' => 'company'
            ],
            [
                'id' => '58458e1c019f8',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'department',
                'shopwareField' => 'department'
            ],
            [
                'id' => '58458e1c0195d',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'salutation',
                'shopwareField' => 'salutation'
            ],
            [
                'id' => '58458e1c01475',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'title',
                'shopwareField' => 'title'
            ],
            [
                'id' => '58458e1c01bb1',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'firstname',
                'shopwareField' => 'firstname'
            ],
            [
                'id' => '58458e1c01246',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'lastname',
                'shopwareField' => 'lastname'
            ],
            [
                'id' => '58458e1c01347',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'street',
                'shopwareField' => 'street'
            ],
            [
                'id' => '58458e1c02067',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'zipcode',
                'shopwareField' => 'zipcode'
            ],
            [
                'id' => '58458e1c01774',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'firstname',
                'shopwareField' => 'firstname'
            ],
            [
                'id' => '58458e1c0193a',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'city',
                'shopwareField' => 'city'
            ],
            [
                'id' => '58458e1c0101c',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'countryID',
                'shopwareField' => 'countryID'
            ],
            [
                'id' => '58458e1c013f4',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'stateID',
                'shopwareField' => 'stateID'
            ],
            [
                'id' => '58458e1c011fc',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'vatID',
                'shopwareField' => 'vatId'
            ],
            [
                'id' => '58458e1c01c2e',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'phone',
                'shopwareField' => 'phone'
            ],
            [
                'id' => '58458e1c014cf',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'additionalAddressLine1',
                'shopwareField' => 'additionalAddressLine1'
            ],
            [
                'id' => '58458e1c02265',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'additionalAddressLine2',
                'shopwareField' => 'additionalAddressLine2'
            ]
        ];
    }
}
