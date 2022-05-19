<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class ArticleImageUrlProfile implements \JsonSerializable, ProfileMetaData
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
        return 'default_article_images_url';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_article_images_url_description';
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
                                                'id' => '55d59f9798545',
                                                'name' => 'image',
                                                'index' => 2,
                                                'type' => 'iteration',
                                                'adapter' => 'image',
                                                'parentKey' => 'articleId',
                                                'shopwareField' => '',
                                                'children' => [
                                                    0 => [
                                                            'id' => '55d59fb16edca',
                                                            'type' => 'leaf',
                                                            'index' => 0,
                                                            'name' => 'imageUrl',
                                                            'shopwareField' => 'imageUrl',
                                                        ],
                                                ],
                                            ],
                                        ],
                                    'attributes' => null,
                                ],
                        ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }
}
