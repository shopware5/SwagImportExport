<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class CategoryTranslationProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::CATEGORIES_TRANSLATION_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_category_translations';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_category_translations_description';
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
                    'name' => 'Translations',
                    'index' => 1,
                    'type' => 'node',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '537359399c90d',
                            'name' => 'Translation',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getTranslationFields(),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getTranslationFields(): array
    {
        return [
            [
                'id' => '5473575d78c54',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'categoryId',
                'shopwareField' => 'categoryId',
            ],
            [
                'id' => '7891675e78c12',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'languageId',
                'shopwareField' => 'languageId',
            ],
            [
                'id' => '5429675d78b28',
                'type' => 'leaf',
                'index' => 2,
                'name' => 'description',
                'shopwareField' => 'description',
            ],
            [
                'id' => '543998726b38e',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'external',
                'shopwareField' => 'external',
            ],
            [
                'id' => '53ce3e8f25a24',
                'index' => 4,
                'type' => 'leaf',
                'name' => 'externalTarget',
                'shopwareField' => 'externalTarget',
            ],
            [
                'id' => '53ce6f9501db7',
                'index' => 5,
                'type' => 'leaf',
                'name' => 'imagePath',
                'shopwareField' => 'imagePath',
            ],
            [
                'id' => '53ce8fa3bd231',
                'index' => 6,
                'type' => 'leaf',
                'name' => 'cmsheadline',
                'shopwareField' => 'cmsheadline',
            ],
            [
                'id' => '53ce9fb6d95d8',
                'index' => 7,
                'type' => 'leaf',
                'name' => 'cmstext',
                'shopwareField' => 'cmstext',
            ],
            [
                'id' => '542a2df925af2',
                'type' => 'leaf',
                'index' => 8,
                'name' => 'metatitle',
                'shopwareField' => 'metatitle',
            ],
            [
                'id' => '543e2df985af3',
                'type' => 'leaf',
                'index' => 9,
                'name' => 'metadescription',
                'shopwareField' => 'metadescription',
            ],
            [
                'id' => '547d2df785af4',
                'type' => 'leaf',
                'index' => 10,
                'name' => 'metakeywords',
                'shopwareField' => 'metakeywords',
            ],
        ];
    }
}
