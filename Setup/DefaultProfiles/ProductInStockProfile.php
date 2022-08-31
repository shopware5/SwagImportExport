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

class ProductInStockProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::PRODUCT_INSTOCK_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_article_in_stock';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_article_in_stock_description';
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
                    'name' => 'articlesInStock',
                    'index' => 1,
                    'type' => 'node',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '537359399c90d',
                            'name' => 'article',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'attributes' => null,
                            'children' => $this->getProductInStockFields(),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getProductInStockFields(): array
    {
        return [
            0 => [
                'id' => '5373865547d06',
                'name' => 'ordernumber',
                'index' => 0,
                'type' => 'leaf',
                'shopwareField' => 'orderNumber',
            ],
            1 => [
                'id' => '537388742e20e',
                'name' => 'instock',
                'index' => 1,
                'type' => 'leaf',
                'shopwareField' => 'inStock',
            ],
            2 => [
                'id' => '541c4b9ddc00e',
                'type' => 'leaf',
                'index' => 2,
                'name' => '_additionaltext',
                'shopwareField' => 'additionalText',
            ],
            3 => [
                'id' => '541c4bc6b7e0a',
                'type' => 'leaf',
                'index' => 3,
                'name' => '_supplier',
                'shopwareField' => 'supplier',
            ],
            4 => [
                'id' => '541c4bd27761c',
                'type' => 'leaf',
                'index' => 4,
                'name' => '_price',
                'shopwareField' => 'price',
            ],
        ];
    }
}
