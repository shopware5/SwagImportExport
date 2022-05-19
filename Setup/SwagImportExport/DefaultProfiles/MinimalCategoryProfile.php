<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class MinimalCategoryProfile implements \JsonSerializable, ProfileMetaData
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
        return 'default_categories_minimal';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_categories_minimal_description';
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
                [
                    'id' => '537359399c80a',
                    'name' => 'Header',
                    'index' => '0',
                    'type' => 'node',
                    'children' => [
                        [
                            'id' => '537385ed7c799',
                            'name' => 'HeaderChild',
                            'index' => '0',
                            'type' => 'node',
                            'shopwareField' => '',
                        ],
                    ],
                ],
                [
                    'id' => '537359399c8b7',
                    'name' => 'categories',
                    'index' => '1',
                    'type' => 'node',
                    'children' => [
                        [
                            'id' => '537359399c90d',
                            'name' => 'category',
                            'index' => '0',
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'attributes' => '',
                            'children' => [
                                $this->getCategoryIdField(),
                                $this->getParentIdField(),
                                $this->getNameField(),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getCategoryIdField()
    {
        return [
            'id' => '53e9f539a997d',
            'type' => 'leaf',
            'index' => '0',
            'name' => 'categoryId',
            'shopwareField' => 'categoryId',
        ];
    }

    /**
     * @return array
     */
    private function getParentIdField()
    {
        return [
            'id' => '53e0a853f1b98',
            'type' => 'leaf',
            'index' => '1',
            'name' => 'parentID',
            'shopwareField' => 'parentId',
        ];
    }

    /**
     * @return array
     */
    private function getNameField()
    {
        return [
            'id' => '57ff840eab2d9',
            'type' => 'leaf',
            'index' => '2',
            'name' => 'name',
            'shopwareField' => 'name',
            'defaultValue' => '',
        ];
    }
}
