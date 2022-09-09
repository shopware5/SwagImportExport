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

class MinimalOrdersProfile implements \JsonSerializable, ProfileMetaData
{
    /**
     * {@inheritdoc}
     */
    public function getAdapter(): string
    {
        return DataDbAdapter::ORDER_ADAPTER;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'default_orders_minimal';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'default_orders_minimal_description';
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
            'children' => [
                [
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
                [
                    'id' => '537359399c8b7',
                    'name' => 'orders',
                    'index' => 1,
                    'type' => '',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '568b80ba299d6',
                            'name' => 'order',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'defaultValue' => '',
                            'children' => $this->getOrderStatusFields(),
                        ],
                    ],
                    'defaultValue' => '',
                ],
            ],
        ];
    }

    private function getOrderStatusFields(): array
    {
        return [
            0 => [
                'id' => '568b80c494f70',
                'type' => 'leaf',
                'index' => 0,
                'name' => 'orderId',
                'shopwareField' => 'orderId',
                'defaultValue' => '',
            ],
            1 => [
                'id' => '568b80cff3bd0',
                'type' => 'leaf',
                'index' => 1,
                'name' => 'number',
                'shopwareField' => 'number',
                'defaultValue' => '',
            ],
            2 => [
                'id' => '568b80d808eda',
                'type' => 'leaf',
                'index' => 2,
                'name' => 'customerId',
                'shopwareField' => 'customerId',
                'defaultValue' => '',
            ],
            3 => [
                'id' => '568b80e3b5e42',
                'type' => 'leaf',
                'index' => 3,
                'name' => 'paymentStatusId',
                'shopwareField' => 'paymentStatusId',
                'defaultValue' => '',
            ],
            4 => [
                'id' => '568b80ed9fdd3',
                'type' => 'leaf',
                'index' => 4,
                'name' => 'status',
                'shopwareField' => 'status',
                'defaultValue' => '',
            ],
        ];
    }
}
