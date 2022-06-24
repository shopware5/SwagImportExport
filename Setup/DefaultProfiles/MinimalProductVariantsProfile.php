<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class MinimalProductVariantsProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::PRODUCT_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_article_variants_minimal';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_article_variants_minimal_description';
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
                    'id' => '4',
                    'name' => 'articles',
                    'index' => 0,
                    'type' => '',
                    'children' => [
                        0 => [
                            'id' => '53e0d3148b0b2',
                            'name' => 'article',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'article',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getProductVariantFields(),
                            'attributes' => null,
                        ],
                    ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }

    private function getProductVariantFields(): array
    {
        return [
            0 => [
                'id' => '53e0d365881b7',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'ordernumber',
                'shopwareField' => 'orderNumber',
            ],
            1 => [
                'id' => '53e0d329364c4',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'mainnumber',
                'shopwareField' => 'mainNumber',
            ],
            2 => [
                'id' => '53e0d3a201785',
                'type' => 'leaf',
                'index' => 2,
                'name' => 'name',
                'shopwareField' => 'name',
            ],
            3 => [
                'id' => '53e0d3fea6646',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'supplier',
                'shopwareField' => 'supplierName',
            ],
            4 => [
                'id' => '53e0d4333dca7',
                'type' => 'leaf',
                'index' => 4,
                'name' => 'tax',
                'shopwareField' => 'tax',
            ],
            5 => [
                'id' => '57a49838a7656',
                'type' => 'leaf',
                'index' => 5,
                'name' => 'kind',
                'shopwareField' => 'kind',
                'defaultValue' => '',
            ],
            6 => [
                'id' => '53e0d44938a70',
                'type' => 'node',
                'index' => 6,
                'name' => 'prices',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '53e0d45110b1d',
                        'name' => 'price',
                        'index' => 0,
                        'type' => 'iteration',
                        'adapter' => 'price',
                        'parentKey' => 'variantId',
                        'shopwareField' => '',
                        'children' => [
                            0 => [
                                'id' => '53eddba5e3471',
                                'type' => 'leaf',
                                'index' => 0,
                                'name' => 'group',
                                'shopwareField' => 'priceGroup',
                            ],
                            1 => [
                                'id' => '53e0d472a0aa8',
                                'type' => 'leaf',
                                'index' => 1,
                                'name' => 'price',
                                'shopwareField' => 'price',
                            ],
                            2 => [
                                'id' => '53e0d48a9313a',
                                'type' => 'leaf',
                                'index' => 2,
                                'name' => 'pseudoprice',
                                'shopwareField' => 'pseudoPrice',
                            ],
                            3 => [
                                'id' => '541af979237a1',
                                'type' => 'leaf',
                                'index' => 3,
                                'name' => 'baseprice',
                                'shopwareField' => 'basePrice',
                            ],
                        ],
                        'attributes' => null,
                    ],
                ],
                'defaultValue' => '',
            ],
            7 => [
                'id' => '53fb272db680f',
                'type' => 'leaf',
                'index' => 7,
                'name' => 'active',
                'shopwareField' => 'active',
                'defaultValue' => 0,
            ],
            8 => [
                'id' => '54211df500e93',
                'name' => 'category',
                'index' => 8,
                'type' => 'iteration',
                'adapter' => 'category',
                'parentKey' => 'articleId',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '54211e05ddc3f',
                        'type' => 'leaf',
                        'index' => 0,
                        'name' => 'categories',
                        'shopwareField' => 'categoryId',
                    ],
                ],
                'defaultValue' => '',
            ],
            9 => [
                'id' => '55d59e2fc0c56',
                'name' => 'configurator',
                'index' => 9,
                'type' => 'iteration',
                'adapter' => 'configurator',
                'parentKey' => 'variantId',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '55d59e3d21483',
                        'type' => 'leaf',
                        'index' => 0,
                        'name' => 'configGroupName',
                        'shopwareField' => 'configGroupName',
                    ],
                    1 => [
                        'id' => '55d59e4b02a39',
                        'type' => 'leaf',
                        'index' => 1,
                        'name' => 'configOptionName',
                        'shopwareField' => 'configOptionName',
                    ],
                ],
                'defaultValue' => '',
            ],
        ];
    }
}
