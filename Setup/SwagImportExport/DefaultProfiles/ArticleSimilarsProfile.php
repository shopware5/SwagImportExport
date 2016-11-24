<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

class ArticleSimilarsProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * @inheritdoc
     */
    public function getAdapter()
    {
        return 'articles';
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'default_similar_articles';
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
                            'children' => $this->getArticleSimilarsFields(),
                            'attributes' => NULL
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
    private function getArticleSimilarsFields()
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
                'id' => '55d5a08438edf',
                'name' => 'similar',
                'index' => 2,
                'type' => 'iteration',
                'adapter' => 'similar',
                'parentKey' => 'articleId',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '55d5a27a4fc67',
                        'type' => 'leaf',
                        'index' => 0,
                        'name' => 'similarId',
                        'shopwareField' => 'similarId'
                    ]
                ]
            ]
        ];
    }
}