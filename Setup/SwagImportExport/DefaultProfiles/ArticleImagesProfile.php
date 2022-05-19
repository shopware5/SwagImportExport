<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class ArticleImagesProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::ARTICLE_IMAGE_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_article_images';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_article_images_description';
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
                    'name' => 'images',
                    'index' => 1,
                    'type' => 'node',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '537359399c90d',
                            'name' => 'image',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getArticleImagesFields(),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function getArticleImagesFields()
    {
        return [
            0 => [
                'id' => '53ff1e618a9ad',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'ordernumber',
                'shopwareField' => 'ordernumber',
            ],
            1 => [
                'id' => '5373865547d06',
                'name' => 'image',
                'index' => 1,
                'type' => 'leaf',
                'shopwareField' => 'image',
            ],
            2 => [
                'id' => '537388742e20e',
                'name' => 'main',
                'index' => 2,
                'type' => 'leaf',
                'shopwareField' => 'main',
            ],
            3 => [
                'id' => '53e39a5fddf41',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'description',
                'shopwareField' => 'description',
            ],
            4 => [
                'id' => '53e39a698522a',
                'type' => 'leaf',
                'index' => 4,
                'name' => 'position',
                'shopwareField' => 'position',
            ],
            5 => [
                'id' => '53e39a737733d',
                'type' => 'leaf',
                'index' => 5,
                'name' => 'width',
                'shopwareField' => 'width',
            ],
            6 => [
                'id' => '53e39a7c1a52e',
                'type' => 'leaf',
                'index' => 6,
                'name' => 'height',
                'shopwareField' => 'height',
            ],
            7 => [
                'id' => '54004e7bf3a1a',
                'type' => 'leaf',
                'index' => 7,
                'name' => 'relations',
                'shopwareField' => 'relations',
            ],
        ];
    }
}
