<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\DefaultProfiles;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;

class ArticleProfile implements \JsonSerializable, ProfileMetaData
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
        return 'default_articles';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'default_articles_description';
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
                '0' => [
                    'id' => '4',
                    'name' => 'articles',
                    'index' => 0,
                    'type' => '',
                    'children' => [
                        '0' => [
                            'id' => '53e0d3148b0b2',
                            'name' => 'article',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'article',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => $this->getArticleFields(),
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
    private function getArticleFields()
    {
        return [
            '0' => [
                'id' => '53e0d365881b7',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'ordernumber',
                'shopwareField' => 'orderNumber',
            ],
            '1' => [
                    'id' => '53e0d329364c4',
                    'type' => 'leaf',
                    'index' => 1,
                    'name' => 'mainnumber',
                    'shopwareField' => 'mainNumber',
                ],
            '2' => [
                    'id' => '53e0d3a201785',
                    'type' => 'leaf',
                    'index' => 2,
                    'name' => 'name',
                    'shopwareField' => 'name',
                ],
            '3' => [
                    'id' => '53fb1c8c99aac',
                    'type' => 'leaf',
                    'index' => 3,
                    'name' => 'additionalText',
                    'shopwareField' => 'additionalText',
                ],
            '4' => [
                    'id' => '53e0d3fea6646',
                    'type' => 'leaf',
                    'index' => 4,
                    'name' => 'supplier',
                    'shopwareField' => 'supplierName',
                ],
            '5' => [
                    'id' => '53e0d4333dca7',
                    'type' => 'leaf',
                    'index' => 5,
                    'name' => 'tax',
                    'shopwareField' => 'tax',
                ],
            '6' => [
                    'id' => '53e0d44938a70',
                    'type' => 'node',
                    'index' => 6,
                    'name' => 'prices',
                    'shopwareField' => '',
                    'children' => [
                            '0' => [
                                    'id' => '53e0d45110b1d',
                                    'name' => 'price',
                                    'index' => 0,
                                    'type' => 'iteration',
                                    'adapter' => 'price',
                                    'parentKey' => 'variantId',
                                    'shopwareField' => '',
                                    'children' => [
                                            '0' => [
                                                    'id' => '53eddba5e3471',
                                                    'type' => 'leaf',
                                                    'index' => 0,
                                                    'name' => 'group',
                                                    'shopwareField' => 'priceGroup',
                                                ],
                                            '1' => [
                                                    'id' => '53e0d472a0aa8',
                                                    'type' => 'leaf',
                                                    'index' => 1,
                                                    'name' => 'price',
                                                    'shopwareField' => 'price',
                                                ],
                                            '2' => [
                                                    'id' => '53e0d48a9313a',
                                                    'type' => 'leaf',
                                                    'index' => 2,
                                                    'name' => 'pseudoprice',
                                                    'shopwareField' => 'pseudoPrice',
                                                ],
                                            '3' => [
                                                    'id' => '541af979237a1',
                                                    'type' => 'leaf',
                                                    'index' => 3,
                                                    'name' => 'baseprice',
                                                    'shopwareField' => 'basePrice',
                                                ],
                                            '4' => [
                                                    'id' => '586f57076422f',
                                                    'type' => 'leaf',
                                                    'index' => 4,
                                                    'name' => 'from',
                                                    'shopwareField' => 'from',
                                                ],
                                            '5' => [
                                                    'id' => '586f5711216ba',
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
            '7' => [
                    'id' => '53fb272db680f',
                    'type' => 'leaf',
                    'index' => 7,
                    'name' => 'active',
                    'shopwareField' => 'active',
                ],
            '8' => [
                    'id' => '53eddc83e7a2e',
                    'type' => 'leaf',
                    'index' => 8,
                    'name' => 'instock',
                    'shopwareField' => 'inStock',
                ],
            '9' => [
                    'id' => '541af5febd073',
                    'type' => 'leaf',
                    'index' => 9,
                    'name' => 'stockmin',
                    'shopwareField' => 'stockMin',
                ],
            '10' => [
                    'id' => '53e0d3e46c923',
                    'type' => 'leaf',
                    'index' => 10,
                    'name' => 'description',
                    'shopwareField' => 'description',
                ],
            '11' => [
                    'id' => '541af5dc189bd',
                    'type' => 'leaf',
                    'index' => 11,
                    'name' => 'description_long',
                    'shopwareField' => 'descriptionLong',
                ],
            '12' => [
                    'id' => '541af601a2874',
                    'type' => 'leaf',
                    'index' => 12,
                    'name' => 'shippingtime',
                    'shopwareField' => 'shippingTime',
                ],
            '13' => [
                    'id' => '541af6bac2305',
                    'type' => 'leaf',
                    'index' => 13,
                    'name' => 'added',
                    'shopwareField' => 'date',
                ],
            '14' => [
                    'id' => '541af75d8a839',
                    'type' => 'leaf',
                    'index' => 14,
                    'name' => 'changed',
                    'shopwareField' => 'changeTime',
                ],
            '15' => [
                    'id' => '541af76ed2c28',
                    'type' => 'leaf',
                    'index' => 15,
                    'name' => 'releasedate',
                    'shopwareField' => 'releaseDate',
                ],
            '16' => [
                    'id' => '541af7a98284d',
                    'type' => 'leaf',
                    'index' => 16,
                    'name' => 'shippingfree',
                    'shopwareField' => 'shippingFree',
                ],
            '17' => [
                    'id' => '541af7d1b1c53',
                    'type' => 'leaf',
                    'index' => 17,
                    'name' => 'topseller',
                    'shopwareField' => 'topSeller',
                ],
            '18' => [
                    'id' => '541af887a00ed',
                    'type' => 'leaf',
                    'index' => 18,
                    'name' => 'keywords',
                    'shopwareField' => 'keywords',
                ],
            '19' => [
                    'id' => '541af7f35d78a',
                    'type' => 'leaf',
                    'index' => 19,
                    'name' => 'minpurchase',
                    'shopwareField' => 'minPurchase',
                ],
            '20' => [
                    'id' => '541af889cfb71',
                    'type' => 'leaf',
                    'index' => 20,
                    'name' => 'purchasesteps',
                    'shopwareField' => 'purchaseSteps',
                ],
            '21' => [
                    'id' => '541af88c05567',
                    'type' => 'leaf',
                    'index' => 21,
                    'name' => 'maxpurchase',
                    'shopwareField' => 'maxPurchase',
                ],
            '22' => [
                    'id' => '541af88e24a40',
                    'type' => 'leaf',
                    'index' => 22,
                    'name' => 'purchaseunit',
                    'shopwareField' => 'purchaseUnit',
                ],
            '23' => [
                    'id' => '541af8907b3e3',
                    'type' => 'leaf',
                    'index' => 23,
                    'name' => 'referenceunit',
                    'shopwareField' => 'referenceUnit',
                ],
            '24' => [
                    'id' => '541af9dd95d11',
                    'type' => 'leaf',
                    'index' => 24,
                    'name' => 'packunit',
                    'shopwareField' => 'packUnit',
                ],
            '25' => [
                    'id' => '541af9e03ba80',
                    'type' => 'leaf',
                    'index' => 25,
                    'name' => 'unitID',
                    'shopwareField' => 'unitId',
                ],
            '26' => [
                    'id' => '541af9e2939b0',
                    'type' => 'leaf',
                    'index' => 26,
                    'name' => 'pricegroupID',
                    'shopwareField' => 'priceGroupId',
                ],
            '27' => [
                    'id' => '541af9e54b365',
                    'type' => 'leaf',
                    'index' => 27,
                    'name' => 'pricegroupActive',
                    'shopwareField' => 'priceGroupActive',
                ],
            '28' => [
                    'id' => '541afad534551',
                    'type' => 'leaf',
                    'index' => 28,
                    'name' => 'laststock',
                    'shopwareField' => 'lastStock',
                ],
            '29' => [
                    'id' => '541afad754eb9',
                    'type' => 'leaf',
                    'index' => 29,
                    'name' => 'suppliernumber',
                    'shopwareField' => 'supplierNumber',
                ],
            '30' => [
                    'id' => '541afad9b7357',
                    'type' => 'leaf',
                    'index' => 30,
                    'name' => 'weight',
                    'shopwareField' => 'weight',
                ],
            '31' => [
                    'id' => '541afadc6536c',
                    'type' => 'leaf',
                    'index' => 31,
                    'name' => 'width',
                    'shopwareField' => 'width',
                ],
            '32' => [
                    'id' => '541afadfb5179',
                    'type' => 'leaf',
                    'index' => 32,
                    'name' => 'height',
                    'shopwareField' => 'height',
                ],
            '33' => [
                    'id' => '541afae631bc8',
                    'type' => 'leaf',
                    'index' => 33,
                    'name' => 'length',
                    'shopwareField' => 'length',
                ],
            '34' => [
                    'id' => '541afae97c6ec',
                    'type' => 'leaf',
                    'index' => 34,
                    'name' => 'ean',
                    'shopwareField' => 'ean',
                ],
            '35' => [
                    'id' => '541afdba8e926',
                    'name' => 'similars',
                    'index' => 35,
                    'type' => 'iteration',
                    'adapter' => 'similar',
                    'parentKey' => 'articleId',
                    'shopwareField' => '',
                    'children' => [
                            '0' => [
                                    'id' => '541afdc37e956',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'similar',
                                    'shopwareField' => 'ordernumber',
                                ],
                        ],
                ],
            '36' => [
                    'id' => '53e0d5f7d03d4',
                    'type' => '',
                    'index' => 36,
                    'name' => 'configurators',
                    'shopwareField' => '',
                    'children' => [
                            '0' => [
                                    'id' => '53e0d603db6b9',
                                    'name' => 'configurator',
                                    'index' => 0,
                                    'type' => 'iteration',
                                    'adapter' => 'configurator',
                                    'parentKey' => 'variantId',
                                    'shopwareField' => '',
                                    'children' => [
                                            '0' => [
                                                    'id' => '542119418283a',
                                                    'type' => 'leaf',
                                                    'index' => 0,
                                                    'name' => 'configuratorsetID',
                                                    'shopwareField' => 'configSetId',
                                                ],
                                            '1' => [
                                                    'id' => '53e0d6142adca',
                                                    'type' => 'leaf',
                                                    'index' => 1,
                                                    'name' => 'configuratortype',
                                                    'shopwareField' => 'configSetType',
                                                ],
                                            '2' => [
                                                    'id' => '53e0d63477bef',
                                                    'type' => 'leaf',
                                                    'index' => 2,
                                                    'name' => 'configuratorGroup',
                                                    'shopwareField' => 'configGroupName',
                                                ],
                                            '3' => [
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
                ],
            '37' => [
                    'id' => '54211df500e93',
                    'name' => 'category',
                    'index' => 37,
                    'type' => 'iteration',
                    'adapter' => 'category',
                    'parentKey' => 'articleId',
                    'shopwareField' => '',
                    'children' => [
                            '0' => [
                                    'id' => '54211e05ddc3f',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'categories',
                                    'shopwareField' => 'categoryId',
                                ],
                        ],
                ],
            '222' => [
                    'id' => '541af887a00ee',
                    'type' => 'leaf',
                    'index' => 18,
                    'name' => 'metatitle',
                    'shopwareField' => 'metaTitle',
                ],
        ];
    }
}
