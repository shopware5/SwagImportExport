<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class MinimalArticleProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::ARTICLE_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_articles_minimal';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_articles_minimal_description';
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
                        'children' => $this->getArticleFields(),
                        'shopwareField' => '',
                    ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getArticleFields()
    {
        return [
            0 => [
                'id' => '53e0d3148b0b2',
                'name' => 'article',
                'index' => 0,
                'type' => 'iteration',
                'adapter' => 'article',
                'parentKey' => '',
                'shopwareField' => '',
                'children' => [
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
                        'id' => '53e0d44938a70',
                        'type' => 'node',
                        'index' => 5,
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
                                    4 => [
                                        'id' => '586f6b291bb01',
                                        'type' => 'leaf',
                                        'index' => 4,
                                        'name' => 'from',
                                        'shopwareField' => 'from',
                                    ],
                                    5 => [
                                        'id' => '586f6b33eed94',
                                        'type' => 'leaf',
                                        'index' => 5,
                                        'name' => 'to',
                                        'shopwareField' => 'to',
                                    ],
                                ],
                                'attributes' => null,
                            ],
                        ],
                    ],
                    6 => [
                        'id' => '53fb272db680f',
                        'type' => 'leaf',
                        'index' => 6,
                        'name' => 'active',
                        'shopwareField' => 'active',
                    ],
                    7 => [
                        'id' => '54211df500e93',
                        'name' => 'category',
                        'index' => 7,
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
                    ],
                ],
                'attributes' => null,
            ],
        ];
    }
}
