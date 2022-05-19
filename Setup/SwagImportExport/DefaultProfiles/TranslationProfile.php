<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;

class TranslationProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter()
    {
        return DataDbAdapter::TRANSLATION_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'default_system_translations';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_system_translations_description';
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
                    'index' => 0,
                    'type' => 'node',
                    'children' => [
                        [
                            'id' => '537385ed7c799',
                            'name' => 'HeaderChild',
                            'index' => 0,
                            'type' => 'node',
                            'shopwareField' => '',
                        ],
                    ],
                ],
                [
                    'id' => '537359399c8b7',
                    'name' => 'Translations',
                    'index' => 1,
                    'type' => 'node',
                    'shopwareField' => '',
                    'children' => [
                        [
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

    /**
     * @return array
     */
    private function getTranslationFields()
    {
        return [
            [
                'id' => '552fbf10a3912',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'objectKey',
                'shopwareField' => 'objectKey',
            ],
            [
                'id' => '53ce5e8f25a24',
                'name' => 'objectType',
                'index' => 1,
                'type' => 'leaf',
                'shopwareField' => 'objectType',
            ],
            [
                'id' => '53ce5f9501db7',
                'name' => 'baseName',
                'index' => 2,
                'type' => 'leaf',
                'shopwareField' => 'baseName',
            ],
            [
                'id' => '552fbde3dcb30',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'name',
                'shopwareField' => 'name',
            ],
            [
                'id' => '53ce5fa3bd231',
                'name' => 'description',
                'index' => 4,
                'type' => 'leaf',
                'shopwareField' => 'description',
            ],
            [
                'id' => '543798726b38e',
                'type' => 'leaf',
                'index' => 5,
                'name' => 'languageId',
                'shopwareField' => 'languageId',
            ],
        ];
    }
}
