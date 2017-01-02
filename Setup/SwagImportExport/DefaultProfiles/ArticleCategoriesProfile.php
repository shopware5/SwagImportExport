<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

class ArticleCategoriesProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * @inheritdoc
     */
    public function getAdapter()
    {
        return DataDbAdapter::ARTICLE_ADAPTER;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'default_article_categories';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'default_article_categories_description';
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
                            'children' => $this->getArticleCategoriesFields(),
                            'attributes' => null
                        ]
                    ],
                    'shopwareField' => ''
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    private function getArticleCategoriesFields()
    {
        return [
            0 => [
                'id' => '53e0d365881b7',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'ordernumber',
                'shopwareField' => 'orderNumber'
            ],
            1 => [
                'id' => '53e0d329364c4',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'mainnumber',
                'shopwareField' => 'mainNumber'
            ],
            2 => [
                'id' => '55ddae346fc40',
                'name' => 'category',
                'index' => 2,
                'type' => 'iteration',
                'adapter' => 'category',
                'parentKey' => 'articleId',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '55ddae3e1313c',
                        'type' => 'leaf',
                        'index' => 0,
                        'name' => 'categoryId',
                        'shopwareField' => 'categoryId'
                    ]
                ]
            ]
        ];
    }
}
