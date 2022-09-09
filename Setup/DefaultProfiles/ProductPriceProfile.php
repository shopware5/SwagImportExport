<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Setup\DefaultProfiles;

use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\Utils\SwagVersionHelper;

class ProductPriceProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::PRODUCT_PRICE_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_article_prices';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_article_prices_description';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' => $this->getProductPriceFields(),
        ];
    }

    private function getProductPriceFields(): array
    {
        $columns = [
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
                'name' => 'Prices',
                'index' => 1,
                'type' => 'node',
                'shopwareField' => '',
                'children' => [
                    0 => [
                        'id' => '537359399c90d',
                        'name' => 'Price',
                        'index' => 0,
                        'type' => 'iteration',
                        'adapter' => 'default',
                        'parentKey' => '',
                        'shopwareField' => '',
                        'children' => [
                            0 => [
                                'id' => '540ff6e624be5',
                                'type' => 'leaf',
                                'index' => 0,
                                'name' => 'ordernumber',
                                'shopwareField' => 'orderNumber',
                            ],
                            1 => [
                                'id' => '540ffb5b14291',
                                'type' => 'leaf',
                                'index' => 1,
                                'name' => 'price',
                                'shopwareField' => 'price',
                            ],
                            2 => [
                                'id' => '540ffb5cea2df',
                                'type' => 'leaf',
                                'index' => 2,
                                'name' => 'pricegroup',
                                'shopwareField' => 'priceGroup',
                            ],
                            3 => [
                                'id' => '540ffb5e68fe5',
                                'type' => 'leaf',
                                'index' => 3,
                                'name' => 'from',
                                'shopwareField' => 'from',
                            ],
                            4 => [
                                'id' => '586f65957f616',
                                'type' => 'leaf',
                                'index' => 4,
                                'name' => 'to',
                                'shopwareField' => 'to',
                            ],
                            5 => [
                                'id' => '540ffb5fd04ba',
                                'type' => 'leaf',
                                'index' => 5,
                                'name' => 'pseudoprice',
                                'shopwareField' => 'pseudoPrice',
                            ],
                            6 => [
                                'id' => '540ffb61558eb',
                                'type' => 'leaf',
                                'index' => 6,
                                'name' => 'purchaseprice',
                                'shopwareField' => 'purchasePrice',
                            ],
                            7 => [
                                'id' => '540ffda5904e5',
                                'type' => 'leaf',
                                'index' => 7,
                                'name' => '_name',
                                'shopwareField' => 'name',
                            ],
                            8 => [
                                'id' => '540ffc1d66042',
                                'type' => 'leaf',
                                'index' => 8,
                                'name' => '_additionaltext',
                                'shopwareField' => 'additionalText',
                            ],
                            9 => [
                                'id' => '540ffcf5089af',
                                'type' => 'leaf',
                                'index' => 9,
                                'name' => '_supplier',
                                'shopwareField' => 'supplierName',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if (SwagVersionHelper::isShopware578()) {
            $columns[1]['children'][0]['children'][] = [
                'id' => '540ffb5fdaaa',
                'type' => 'leaf',
                'index' => 10,
                'name' => 'regulationPrice',
                'shopwareField' => 'regulationPrice',
            ];
        }

        return $columns;
    }
}
