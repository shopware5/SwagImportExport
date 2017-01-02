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
 * Class ArticleTranslationProfile
 * @package Shopware\Setup\SwagImportExport\DefaultProfiles
 */
class ArticleTranslationProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * @inheritdoc
     */
    public function getAdapter()
    {
        return DataDbAdapter::ARTICLE_TRANSLATION_ADAPTER;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'default_article_translations';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'default_article_translations_description';
    }

    /**
     * @inheritdoc
     */
    function jsonSerialize()
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' =>
                [
                    0 =>
                        [
                            'id' => '537359399c80a',
                            'name' => 'Header',
                            'index' => 0,
                            'type' => 'node',
                            'children' =>
                                [
                                    0 =>
                                        [
                                            'id' => '537385ed7c799',
                                            'name' => 'HeaderChild',
                                            'index' => 0,
                                            'type' => 'node',
                                            'shopwareField' => '',
                                        ]
                                ]
                        ],
                    1 =>
                        [
                            'id' => '537359399c8b7',
                            'name' => 'Translations',
                            'index' => 1,
                            'type' => 'node',
                            'shopwareField' => '',
                            'children' =>
                                [
                                    0 =>
                                        [
                                            'id' => '537359399c90d',
                                            'name' => 'Translation',
                                            'index' => 0,
                                            'type' => 'iteration',
                                            'adapter' => 'default',
                                            'parentKey' => '',
                                            'shopwareField' => '',
                                            'children' => $this->getArticleTranslationFields()
                                        ]
                                ]
                        ]
                ]
        ];
    }

    private function getArticleTranslationFields()
    {
        return [
            0 =>
                [
                    'id' => '5429676d78b28',
                    'type' => 'leaf',
                    'index' => 0,
                    'name' => 'articlenumber',
                    'shopwareField' => 'articleNumber',
                ],
            1 =>
                [
                    'id' => '543798726b38e',
                    'type' => 'leaf',
                    'index' => 1,
                    'name' => 'languageId',
                    'shopwareField' => 'languageId',
                ],
            2 =>
                [
                    'id' => '53ce5e8f25a24',
                    'name' => 'name',
                    'index' => 2,
                    'type' => 'leaf',
                    'shopwareField' => 'name',
                ],
            3 =>
                [
                    'id' => '53ce5f9501db7',
                    'name' => 'description',
                    'index' => 3,
                    'type' => 'leaf',
                    'shopwareField' => 'description',
                ],
            4 =>
                [
                    'id' => '53ce5fa3bd231',
                    'name' => 'longdescription',
                    'index' => 4,
                    'type' => 'leaf',
                    'shopwareField' => 'descriptionLong',
                ],
            5 =>
                [
                    'id' => '53ce5fb6d95d8',
                    'name' => 'keywords',
                    'index' => 5,
                    'type' => 'leaf',
                    'shopwareField' => 'keywords',
                ],
            6 =>
                [
                    'id' => '542a5df925af2',
                    'type' => 'leaf',
                    'index' => 6,
                    'name' => 'metatitle',
                    'shopwareField' => 'metaTitle',
                ]
        ];
    }
}
