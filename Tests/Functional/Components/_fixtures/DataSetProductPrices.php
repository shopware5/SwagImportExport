<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\_fixtures;

class DataSetProductPrices
{
    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public static function getDataSet(): array
    {
        return [
            'default' => [
                [
                    'ordernumber' => 'SW10239',
                    'price' => '196,5',
                    'priceGroup' => 'EK',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoprice' => '262',
                    '_name' => 'TEST-Product1',
                    'regulationPrice' => '205,5',
                ],
                [
                    'ordernumber' => 'SW10239',
                    'price' => '170,3',
                    'pricegroup' => 'H2',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoprice' => '262',
                    '_name' => 'TEST-Product1',
                    'regulationPrice' => '178,1',
                ],
                [
                    'ordernumber' => 'SW10239',
                    'price' => '163,75',
                    'pricegroup' => 'H3',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoprice' => '262',
                    '_name' => 'TEST-Product1',
                    'regulationPrice' => '171,25',
                ],
                [
                    'ordernumber' => 'SW10239',
                    'price' => '157,2',
                    'pricegroup' => 'H4',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoprice' => '262',
                    '_name' => 'TEST-Product1',
                    'regulationPrice' => '164,4',
                ],
                [
                    'ordernumber' => 'SW10239',
                    'price' => '150,65',
                    'pricegroup' => 'H5',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoprice' => '262',
                    '_name' => 'TEST-Product1',
                    'regulationPrice' => '157,55',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public static function getFixedDataSet(): array
    {
        return [
            'default' => [
                [
                    'orderNumber' => 'SW10239',
                    'price' => '196.5',
                    'priceGroup' => 'EK',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoPrice' => '262',
                    'purchasePrice' => '',
                    'name' => 'TEST-Product1',
                    'additionalText' => '',
                    'supplierName' => '',
                    'regulationPrice' => '205.5',
                ],
                [
                    'orderNumber' => 'SW10239',
                    'price' => '170.3',
                    'priceGroup' => 'H2',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoPrice' => '262',
                    'purchasePrice' => '',
                    'name' => 'TEST-Product1',
                    'additionalText' => '',
                    'supplierName' => '',
                    'regulationPrice' => '178.1',
                ],
                [
                    'orderNumber' => 'SW10239',
                    'price' => '163.75',
                    'priceGroup' => 'H3',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoPrice' => '262',
                    'purchasePrice' => '',
                    'name' => 'TEST-Product1',
                    'additionalText' => '',
                    'supplierName' => '',
                    'regulationPrice' => '171.25',
                ],
                [
                    'orderNumber' => 'SW10239',
                    'price' => '157.2',
                    'priceGroup' => 'H4',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoPrice' => '262',
                    'purchasePrice' => '',
                    'name' => 'TEST-Product1',
                    'additionalText' => '',
                    'supplierName' => '',
                    'regulationPrice' => '164.4',
                ],
                [
                    'orderNumber' => 'SW10239',
                    'price' => '150.65',
                    'priceGroup' => 'H5',
                    'from' => '1',
                    'to' => 'beliebig',
                    'pseudoPrice' => '262',
                    'purchasePrice' => '',
                    'name' => 'TEST-Product1',
                    'additionalText' => '',
                    'supplierName' => '',
                    'regulationPrice' => '157.55',
                ],
            ],
        ];
    }
}
