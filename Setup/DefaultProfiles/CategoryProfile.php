<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class CategoryProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::CATEGORIES_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_categories';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_categories_description';
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
                '0' => [
                    'id' => '537359399c80a',
                    'name' => 'Header',
                    'index' => '0',
                    'type' => 'node',
                    'children' => [
                        '0' => [
                            'id' => '537385ed7c799',
                            'name' => 'HeaderChild',
                            'index' => '0',
                            'type' => 'node',
                        ],
                    ],
                ],
                '1' => [
                    'id' => '537359399c8b7',
                    'name' => 'categories',
                    'index' => '1',
                    'type' => 'node',
                    'children' => [
                        '0' => [
                            'id' => '537359399c90d',
                            'name' => 'category',
                            'index' => '0',
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'children' => $this->getCategoryFields(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getCategoryFields()
    {
        return [
            '0' => [
                'id' => '53e9f539a997d',
                'type' => 'leaf',
                'index' => '0',
                'name' => 'categoryId',
                'shopwareField' => 'categoryId',
            ],
            '1' => [
                'id' => '53e0a853f1b98',
                'type' => 'leaf',
                'index' => '1',
                'name' => 'parentID',
                'shopwareField' => 'parentId',
            ],
            '2' => [
                'id' => '53e0cf5cad595',
                'type' => 'leaf',
                'index' => '2',
                'name' => 'description',
                'shopwareField' => 'name',
            ],
            '3' => [
                'id' => '53e9f69bf2edb',
                'type' => 'leaf',
                'index' => '3',
                'name' => 'position',
                'shopwareField' => 'position',
            ],
            '4' => [
                'id' => '53e0d1414b0ad',
                'type' => 'leaf',
                'index' => '4',
                'name' => 'metatitle',
                'shopwareField' => 'metaTitle',
            ],
            '5' => [
                'id' => '53e0d1414b0d7',
                'type' => 'leaf',
                'index' => '5',
                'name' => 'metakeywords',
                'shopwareField' => 'metaKeywords',
            ],
            '6' => [
                'id' => '53e0d17da1f06',
                'type' => 'leaf',
                'index' => '6',
                'name' => 'metadescription',
                'shopwareField' => 'metaDescription',
            ],
            '7' => [
                'id' => '53e9f5c0eedaf',
                'type' => 'leaf',
                'index' => '7',
                'name' => 'cmsheadline',
                'shopwareField' => 'cmsHeadline',
            ],
            '8' => [
                'id' => '53e9f5d80f10f',
                'type' => 'leaf',
                'index' => '8',
                'name' => 'cmstext',
                'shopwareField' => 'cmsText',
            ],
            '9' => [
                'id' => '53e9f5e603ffe',
                'type' => 'leaf',
                'index' => '9',
                'name' => 'template',
                'shopwareField' => 'template',
            ],
            '10' => [
                'id' => '53e9f5f87c87a',
                'type' => 'leaf',
                'index' => '10',
                'name' => 'active',
                'shopwareField' => 'active',
            ],
            '11' => [
                'id' => '53e9f609c56eb',
                'type' => 'leaf',
                'index' => '11',
                'name' => 'blog',
                'shopwareField' => 'blog',
            ],
            '12' => [
                'id' => '53e9f62a03f55',
                'type' => 'leaf',
                'index' => '13',
                'name' => 'external',
                'shopwareField' => 'external',
            ],
            '13' => [
                'id' => '53e9f637aa1fe',
                'type' => 'leaf',
                'index' => '14',
                'name' => 'hidefilter',
                'shopwareField' => 'hideFilter',
            ],
            '14' => [
                'id' => '541c35c378bc9',
                'type' => 'leaf',
                'index' => '15',
                'name' => 'attribute_attribute1',
                'shopwareField' => 'attributeAttribute1',
            ],
            '15' => [
                'id' => '541c36d0bba0f',
                'type' => 'leaf',
                'index' => '16',
                'name' => 'attribute_attribute2',
                'shopwareField' => 'attributeAttribute2',
            ],
            '16' => [
                'id' => '541c36d63fac6',
                'type' => 'leaf',
                'index' => '17',
                'name' => 'attribute_attribute3',
                'shopwareField' => 'attributeAttribute3',
            ],
            '17' => [
                'id' => '541c36da52222',
                'type' => 'leaf',
                'index' => '18',
                'name' => 'attribute_attribute4',
                'shopwareField' => 'attributeAttribute4',
            ],
            '18' => [
                'id' => '541c36dc540e3',
                'type' => 'leaf',
                'index' => '19',
                'name' => 'attribute_attribute5',
                'shopwareField' => 'attributeAttribute5',
            ],
            '19' => [
                'id' => '541c36dd9e130',
                'type' => 'leaf',
                'index' => '20',
                'name' => 'attribute_attribute6',
                'shopwareField' => 'attributeAttribute6',
            ],
            '20' => [
                'id' => '54dc86ff4bee5',
                'name' => 'CustomerGroups',
                'index' => '21',
                'type' => 'iteration',
                'adapter' => 'customerGroups',
                'parentKey' => 'categoryId',
                'children' => [
                    '0' => [
                        'id' => '54dc87118ad11',
                        'type' => 'leaf',
                        'index' => '0',
                        'name' => 'CustomerGroup',
                        'shopwareField' => 'customerGroupId',
                    ],
                ],
            ],
        ];
    }
}
