<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

/**
 * Class ArticleTranslationUpdateProfile
 * @package Shopware\Setup\SwagImportExport\DefaultProfiles
 */
class ArticleTranslationUpdateProfile implements \JsonSerializable, ProfileMetaData
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
        return 'default_article_translations_update';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'default_article_translations_update_description';
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
            'children' =>
                [
                    0 =>
                        [
                            'id' => '4',
                            'name' => 'articles',
                            'index' => 0,
                            'type' => '',
                            'children' =>
                                [
                                    0 =>
                                        [
                                            'id' => '53e0d3148b0b2',
                                            'name' => 'article',
                                            'index' => 0,
                                            'type' => 'iteration',
                                            'adapter' => 'article',
                                            'parentKey' => '',
                                            'shopwareField' => '',
                                            'children' =>
                                                [
                                                    0 =>
                                                        [
                                                            'id' => '53e0d365881b7',
                                                            'type' => 'leaf',
                                                            'index' => 0,
                                                            'name' => 'ordernumber',
                                                            'shopwareField' => 'orderNumber',
                                                        ],
                                                    1 =>
                                                        [
                                                            'id' => '53e0d329364c4',
                                                            'type' => 'leaf',
                                                            'index' => 1,
                                                            'name' => 'mainnumber',
                                                            'shopwareField' => 'mainNumber',
                                                        ],
                                                    2 =>
                                                        [
                                                            'id' => '55ddb1813e917',
                                                            'name' => 'translation',
                                                            'index' => 2,
                                                            'type' => 'iteration',
                                                            'adapter' => 'translation',
                                                            'parentKey' => 'variantId',
                                                            'shopwareField' => '',
                                                            'children' => $this->getTranslationFields()
                                                        ]
                                                ],
                                            'attributes' => NULL
                                        ]
                                ],
                            'shopwareField' => ''
                        ]
                ]
        ];
    }

    private function getTranslationFields()
    {
        return [
            0 =>
                [
                    'id' => '55ddb18975737',
                    'type' => 'leaf',
                    'index' => 0,
                    'name' => 'languageId',
                    'shopwareField' => 'languageId'
                ],
            1 =>
                [
                    'id' => '55def1344b49a',
                    'type' => 'leaf',
                    'index' => 1,
                    'name' => 'translationname',
                    'shopwareField' => 'name'
                ],
            2 =>
                [
                    'id' => '55ddb19ce4d72',
                    'type' => 'leaf',
                    'index' => 2,
                    'name' => 'translatedescriptionLong',
                    'shopwareField' => 'descriptionLong'
                ],
            3 =>
                [
                    'id' => '55def112b9991',
                    'type' => 'leaf',
                    'index' => 3,
                    'name' => 'translatedescription',
                    'shopwareField' => 'description'
                ]
        ];
    }
}
