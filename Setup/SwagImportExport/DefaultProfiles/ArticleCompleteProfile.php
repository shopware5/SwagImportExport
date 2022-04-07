<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

class ArticleCompleteProfile implements ProfileMetaData, \JsonSerializable
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
        return 'default_articles_complete';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_articles_complete_description';
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
                                                            'id' => '53e0d3a201785',
                                                            'type' => 'leaf',
                                                            'index' => 2,
                                                            'name' => 'name',
                                                            'shopwareField' => 'name',
                                                        ],
                                                    3 => [
                                                            'id' => '53fb1c8c99aac',
                                                            'type' => 'leaf',
                                                            'index' => 3,
                                                            'name' => 'additionalText',
                                                            'shopwareField' => 'additionalText',
                                                        ],
                                                    4 => [
                                                            'id' => '53e0d3fea6646',
                                                            'type' => 'leaf',
                                                            'index' => 4,
                                                            'name' => 'supplier',
                                                            'shopwareField' => 'supplierName',
                                                        ],
                                                    5 => [
                                                            'id' => '53e0d4333dca7',
                                                            'type' => 'leaf',
                                                            'index' => 5,
                                                            'name' => 'tax',
                                                            'shopwareField' => 'tax',
                                                        ],
                                                    6 => $this->getPriceFields(),
                                                    7 => [
                                                            'id' => '53fb272db680f',
                                                            'type' => 'leaf',
                                                            'index' => 7,
                                                            'name' => 'active',
                                                            'shopwareField' => 'active',
                                                        ],
                                                    8 => [
                                                            'id' => '53eddc83e7a2e',
                                                            'type' => 'leaf',
                                                            'index' => 8,
                                                            'name' => 'instock',
                                                            'shopwareField' => 'inStock',
                                                        ],
                                                    9 => [
                                                            'id' => '541af5febd073',
                                                            'type' => 'leaf',
                                                            'index' => 9,
                                                            'name' => 'stockmin',
                                                            'shopwareField' => 'stockMin',
                                                        ],
                                                    10 => [
                                                            'id' => '53e0d3e46c923',
                                                            'type' => 'leaf',
                                                            'index' => 10,
                                                            'name' => 'description',
                                                            'shopwareField' => 'description',
                                                        ],
                                                    11 => [
                                                            'id' => '541af5dc189bd',
                                                            'type' => 'leaf',
                                                            'index' => 11,
                                                            'name' => 'description_long',
                                                            'shopwareField' => 'descriptionLong',
                                                        ],
                                                    12 => [
                                                            'id' => '541af601a2874',
                                                            'type' => 'leaf',
                                                            'index' => 12,
                                                            'name' => 'shippingtime',
                                                            'shopwareField' => 'shippingTime',
                                                        ],
                                                    13 => [
                                                            'id' => '541af6bac2305',
                                                            'type' => 'leaf',
                                                            'index' => 13,
                                                            'name' => 'added',
                                                            'shopwareField' => 'date',
                                                        ],
                                                    14 => [
                                                            'id' => '541af75d8a839',
                                                            'type' => 'leaf',
                                                            'index' => 14,
                                                            'name' => 'changed',
                                                            'shopwareField' => 'changeTime',
                                                        ],
                                                    15 => [
                                                            'id' => '541af76ed2c28',
                                                            'type' => 'leaf',
                                                            'index' => 15,
                                                            'name' => 'releasedate',
                                                            'shopwareField' => 'releaseDate',
                                                        ],
                                                    16 => [
                                                            'id' => '541af7a98284d',
                                                            'type' => 'leaf',
                                                            'index' => 16,
                                                            'name' => 'shippingfree',
                                                            'shopwareField' => 'shippingFree',
                                                        ],
                                                    17 => [
                                                            'id' => '541af7d1b1c53',
                                                            'type' => 'leaf',
                                                            'index' => 17,
                                                            'name' => 'topseller',
                                                            'shopwareField' => 'topSeller',
                                                        ],
                                                    18 => [
                                                            'id' => '541af887a00ed',
                                                            'type' => 'leaf',
                                                            'index' => 18,
                                                            'name' => 'keywords',
                                                            'shopwareField' => 'keywords',
                                                        ],
                                                    19 => [
                                                            'id' => '541af7f35d78a',
                                                            'type' => 'leaf',
                                                            'index' => 19,
                                                            'name' => 'minpurchase',
                                                            'shopwareField' => 'minPurchase',
                                                        ],
                                                    20 => [
                                                            'id' => '541af889cfb71',
                                                            'type' => 'leaf',
                                                            'index' => 20,
                                                            'name' => 'purchasesteps',
                                                            'shopwareField' => 'purchaseSteps',
                                                        ],
                                                    21 => [
                                                            'id' => '541af88c05567',
                                                            'type' => 'leaf',
                                                            'index' => 21,
                                                            'name' => 'maxpurchase',
                                                            'shopwareField' => 'maxPurchase',
                                                        ],
                                                    22 => [
                                                            'id' => '541af88e24a40',
                                                            'type' => 'leaf',
                                                            'index' => 22,
                                                            'name' => 'purchaseunit',
                                                            'shopwareField' => 'purchaseUnit',
                                                        ],
                                                    23 => [
                                                            'id' => '541af8907b3e3',
                                                            'type' => 'leaf',
                                                            'index' => 23,
                                                            'name' => 'referenceunit',
                                                            'shopwareField' => 'referenceUnit',
                                                        ],
                                                    24 => [
                                                            'id' => '541af9dd95d11',
                                                            'type' => 'leaf',
                                                            'index' => 24,
                                                            'name' => 'packunit',
                                                            'shopwareField' => 'packUnit',
                                                        ],
                                                    25 => [
                                                            'id' => '541af9e03ba80',
                                                            'type' => 'leaf',
                                                            'index' => 25,
                                                            'name' => 'unitID',
                                                            'shopwareField' => 'unitId',
                                                        ],
                                                    26 => [
                                                            'id' => '541af9e2939b0',
                                                            'type' => 'leaf',
                                                            'index' => 26,
                                                            'name' => 'pricegroupID',
                                                            'shopwareField' => 'priceGroupId',
                                                        ],
                                                    27 => [
                                                            'id' => '541af9e54b365',
                                                            'type' => 'leaf',
                                                            'index' => 27,
                                                            'name' => 'pricegroupActive',
                                                            'shopwareField' => 'priceGroupActive',
                                                        ],
                                                    28 => [
                                                            'id' => '541afad534551',
                                                            'type' => 'leaf',
                                                            'index' => 28,
                                                            'name' => 'laststock',
                                                            'shopwareField' => 'lastStock',
                                                        ],
                                                    29 => [
                                                            'id' => '541afad754eb9',
                                                            'type' => 'leaf',
                                                            'index' => 29,
                                                            'name' => 'suppliernumber',
                                                            'shopwareField' => 'supplierNumber',
                                                        ],
                                                    30 => [
                                                            'id' => '541afad9b7357',
                                                            'type' => 'leaf',
                                                            'index' => 30,
                                                            'name' => 'weight',
                                                            'shopwareField' => 'weight',
                                                        ],
                                                    31 => [
                                                            'id' => '541afadc6536c',
                                                            'type' => 'leaf',
                                                            'index' => 31,
                                                            'name' => 'width',
                                                            'shopwareField' => 'width',
                                                        ],
                                                    32 => [
                                                            'id' => '541afadfb5179',
                                                            'type' => 'leaf',
                                                            'index' => 32,
                                                            'name' => 'height',
                                                            'shopwareField' => 'height',
                                                        ],
                                                    33 => [
                                                            'id' => '541afae631bc8',
                                                            'type' => 'leaf',
                                                            'index' => 33,
                                                            'name' => 'length',
                                                            'shopwareField' => 'length',
                                                        ],
                                                    34 => [
                                                            'id' => '541afae97c6ec',
                                                            'type' => 'leaf',
                                                            'index' => 34,
                                                            'name' => 'ean',
                                                            'shopwareField' => 'ean',
                                                        ],
                                                    35 => $this->getSimilarFields(),
                                                    36 => $this->getConfiguratorFields(),
                                                    37 => $this->getCategoryFields(),
                                                    39 => $this->getPropertyValueFields(),
                                                    40 => $this->getAccessoryFields(),
                                                    41 => $this->getTranslationFields(),
                                                    42 => $this->getImagesFields(),
                                                    43 => [
                                                            'id' => '582f1666e3f7b',
                                                            'type' => 'leaf',
                                                            'index' => 43,
                                                            'name' => 'attr1',
                                                            'shopwareField' => 'attributeAttr1',
                                                            'defaultValue' => '',
                                                        ],
                                                    44 => [
                                                            'id' => '582f169abcfdc',
                                                            'type' => 'leaf',
                                                            'index' => 44,
                                                            'name' => 'attr2',
                                                            'shopwareField' => 'attributeAttr2',
                                                            'defaultValue' => '',
                                                        ],
                                                    45 => [
                                                            'id' => '582f16aa48589',
                                                            'type' => 'leaf',
                                                            'index' => 45,
                                                            'name' => 'attr3',
                                                            'shopwareField' => 'attributeAttr3',
                                                            'defaultValue' => '',
                                                        ],
                                                    46 => [
                                                            'id' => '584176d60f4a4',
                                                            'type' => 'leaf',
                                                            'index' => 46,
                                                            'name' => 'purchasePrice',
                                                            'shopwareField' => 'purchasePrice',
                                                            'defaultValue' => '',
                                                        ],
                                                    47 => [
                                                        'id' => '584as6d60f4a4',
                                                        'type' => 'leaf',
                                                        'index' => 46,
                                                        'name' => 'regulationPrice',
                                                        'shopwareField' => 'regulationPrice',
                                                        'defaultValue' => '',
                                                    ],
                                                    1600 => [
                                                            'id' => '541af887a00ee',
                                                            'type' => 'leaf',
                                                            'index' => 18,
                                                            'name' => 'metatitle',
                                                            'shopwareField' => 'metaTitle',
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

    /**
     * @return array
     */
    private function getPriceFields()
    {
        return [
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
                                    4 => [
                                            'id' => '586f64abcf438',
                                            'type' => 'leaf',
                                            'index' => 4,
                                            'name' => 'from',
                                            'shopwareField' => 'from',
                                        ],
                                    5 => [
                                            'id' => '586f64b4a75c0',
                                            'type' => 'leaf',
                                            'index' => 5,
                                            'name' => 'to',
                                            'shopwareField' => 'to',
                                        ],
                                ],
                            'attributes' => null,
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getConfiguratorFields()
    {
        return [
            'id' => '53e0d5f7d03d4',
            'type' => '',
            'index' => 36,
            'name' => 'configurators',
            'shopwareField' => '',
            'children' => [
                    0 => [
                            'id' => '53e0d603db6b9',
                            'name' => 'configurator',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'configurator',
                            'parentKey' => 'variantId',
                            'shopwareField' => '',
                            'children' => [
                                    0 => [
                                            'id' => '542119418283a',
                                            'type' => 'leaf',
                                            'index' => 0,
                                            'name' => 'configuratorsetID',
                                            'shopwareField' => 'configSetId',
                                        ],
                                    1 => [
                                            'id' => '53e0d6142adca',
                                            'type' => 'leaf',
                                            'index' => 1,
                                            'name' => 'configuratortype',
                                            'shopwareField' => 'configSetType',
                                        ],
                                    2 => [
                                            'id' => '53e0d63477bef',
                                            'type' => 'leaf',
                                            'index' => 2,
                                            'name' => 'configuratorGroup',
                                            'shopwareField' => 'configGroupName',
                                        ],
                                    3 => [
                                            'id' => '53e0d6446940d',
                                            'type' => 'leaf',
                                            'index' => 3,
                                            'name' => 'configuratorOptions',
                                            'shopwareField' => 'configOptionName',
                                        ],
                                ],
                            'attributes' => null,
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getSimilarFields()
    {
        return [
            'id' => '541afdba8e926',
            'name' => 'similars',
            'index' => 35,
            'type' => 'iteration',
            'adapter' => 'similar',
            'parentKey' => 'articleId',
            'shopwareField' => '',
            'children' => [
                    0 => [
                            'id' => '541afdc37e956',
                            'type' => 'leaf',
                            'index' => 0,
                            'name' => 'similar',
                            'shopwareField' => 'ordernumber',
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getPropertyValueFields()
    {
        return [
            'id' => '582d9f2dc9dc4',
            'name' => 'propertyValue',
            'index' => 39,
            'type' => 'iteration',
            'adapter' => 'propertyValue',
            'parentKey' => 'articleId',
            'shopwareField' => '',
            'defaultValue' => '',
            'children' => [
                    0 => [
                            'id' => '582db0454fe07',
                            'type' => 'leaf',
                            'index' => 0,
                            'name' => 'propertyGroupName',
                            'shopwareField' => 'propertyGroupName',
                            'defaultValue' => '',
                        ],
                    1 => [
                            'id' => '582db04e2239e',
                            'type' => 'leaf',
                            'index' => 1,
                            'name' => 'propertyValueName',
                            'shopwareField' => 'propertyValueName',
                            'defaultValue' => '',
                        ],
                    2 => [
                            'id' => '582db0592926d',
                            'type' => 'leaf',
                            'index' => 2,
                            'name' => 'propertyOptionName',
                            'shopwareField' => 'propertyOptionName',
                            'defaultValue' => '',
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getAccessoryFields()
    {
        return [
            'id' => '582d9f40109da',
            'name' => 'accessory',
            'index' => 40,
            'type' => 'iteration',
            'adapter' => 'accessory',
            'parentKey' => 'articleId',
            'shopwareField' => '',
            'defaultValue' => '',
            'children' => [
                    0 => [
                            'id' => '582db879a1c20',
                            'type' => 'leaf',
                            'index' => 0,
                            'name' => 'accessory',
                            'shopwareField' => 'ordernumber',
                            'defaultValue' => '',
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
            'id' => '582d9f63d5128',
            'name' => 'translation',
            'index' => 41,
            'type' => 'iteration',
            'adapter' => 'translation',
            'parentKey' => 'variantId',
            'shopwareField' => '',
            'defaultValue' => '',
            'children' => [
                    0 => [
                            'id' => '582db8f037ea7',
                            'type' => 'leaf',
                            'index' => 0,
                            'name' => 'variantId',
                            'shopwareField' => 'variantId',
                            'defaultValue' => '',
                        ],
                    1 => [
                            'id' => '582db8fa87d03',
                            'type' => 'leaf',
                            'index' => 1,
                            'name' => 'articleId',
                            'shopwareField' => 'articleId',
                            'defaultValue' => '',
                        ],
                    2 => [
                            'id' => '582db9006cca1',
                            'type' => 'leaf',
                            'index' => 2,
                            'name' => 'name',
                            'shopwareField' => 'name',
                            'defaultValue' => '',
                        ],
                    3 => [
                            'id' => '582db907411a5',
                            'type' => 'leaf',
                            'index' => 3,
                            'name' => 'keywords',
                            'shopwareField' => 'keywords',
                            'defaultValue' => '',
                        ],
                    4 => [
                            'id' => '582db90f68554',
                            'type' => 'leaf',
                            'index' => 4,
                            'name' => 'metaTitle',
                            'shopwareField' => 'metaTitle',
                            'defaultValue' => '',
                        ],
                    5 => [
                            'id' => '582db91702e75',
                            'type' => 'leaf',
                            'index' => 5,
                            'name' => 'description',
                            'shopwareField' => 'description',
                            'defaultValue' => '',
                        ],
                    6 => [
                            'id' => '582db9216e612',
                            'type' => 'leaf',
                            'index' => 6,
                            'name' => 'descriptionLong',
                            'shopwareField' => 'descriptionLong',
                            'defaultValue' => '',
                        ],
                    7 => [
                            'id' => '582db928bc179',
                            'type' => 'leaf',
                            'index' => 7,
                            'name' => 'additionalText',
                            'shopwareField' => 'additionalText',
                            'defaultValue' => '',
                        ],
                    8 => [
                            'id' => '582db933acd79',
                            'type' => 'leaf',
                            'index' => 8,
                            'name' => 'packUnit',
                            'shopwareField' => 'packUnit',
                            'defaultValue' => '',
                        ],
                    9 => [
                            'id' => '582db951b199a',
                            'type' => 'leaf',
                            'index' => 9,
                            'name' => 'attr1',
                            'shopwareField' => 'attr1',
                            'defaultValue' => '',
                        ],
                    10 => [
                            'id' => '582db95ce1bff',
                            'type' => 'leaf',
                            'index' => 10,
                            'name' => 'attr2',
                            'shopwareField' => 'attr2',
                            'defaultValue' => '',
                        ],
                    11 => [
                            'id' => '582db963eaf56',
                            'type' => 'leaf',
                            'index' => 11,
                            'name' => 'attr3',
                            'shopwareField' => 'attr3',
                            'defaultValue' => '',
                        ],
                    12 => [
                            'id' => '5832afe3dfe9a',
                            'type' => 'leaf',
                            'index' => 12,
                            'name' => 'languageId',
                            'shopwareField' => 'languageId',
                            'defaultValue' => '',
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getImagesFields()
    {
        return [
            'id' => '582d9f8997e45',
            'name' => 'image',
            'index' => 42,
            'type' => 'iteration',
            'adapter' => 'image',
            'parentKey' => 'articleId',
            'shopwareField' => '',
            'defaultValue' => '',
            'children' => [
                    0 => [
                            'id' => '582db88eb25c4',
                            'type' => 'leaf',
                            'index' => 0,
                            'name' => 'imageUrl',
                            'shopwareField' => 'imageUrl',
                            'defaultValue' => '',
                        ],
                    1 => [
                            'id' => '582db8ba1d4ad',
                            'type' => 'leaf',
                            'index' => 1,
                            'name' => 'main',
                            'shopwareField' => 'main',
                            'defaultValue' => '',
                        ],
                ],
        ];
    }

    /**
     * @return array
     */
    private function getCategoryFields()
    {
        return [
            'id' => '54211df500e93',
            'name' => 'category',
            'index' => 37,
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
        ];
    }
}
