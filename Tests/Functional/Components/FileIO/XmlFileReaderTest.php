<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\FileIO;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\FileIO\XmlFileReader;

class XmlFileReaderTest extends TestCase
{
    public const IMPORT_FILES_DIR = __DIR__ . '/../../../Helper/ImportFiles/';

    public function testWrongFiletype(): void
    {
        $tree = $this->getDefaultProductTree();
        $count = $this->getReaderCount($tree, 'ArticleImport.csv');

        static::assertEquals(0, $count);
    }

    public function testDefaultProductCount(): void
    {
        $tree = $this->getDefaultProductTree();
        $count = $this->getReaderCount($tree, 'ArticleImport.xml');

        static::assertEquals(2, $count);
    }

    public function testDefaultProductsRead(): void
    {
        $tree = $this->getDefaultProductTree();
        $records = $this->getReaderRecords($tree, 'ArticleImport.xml');

        static::assertCount(2, $records);

        static::assertEquals('Test Supplier', $records[0]['supplier']);
        static::assertEquals('My_Article_With_Variants', $records[1]['name']);
        static::assertEquals(1, $records[1]['minpurchase']);
        static::assertEquals('Set-SW10002', $records[1]['configurators']['configurator'][0]['configSetName']);
    }

    public function testMinimalProductCount(): void
    {
        $tree = $this->getMinimalProductTree();
        $count = $this->getReaderCount($tree, 'ArticleImport.xml');

        static::assertEquals(2, $count);
    }

    public function testMinimalProductsRead(): void
    {
        $tree = $this->getMinimalProductTree();
        $records = $this->getReaderRecords($tree, 'ArticleImport.xml', 1, 1);

        static::assertCount(1, $records);
        static::assertEquals('test_SW10002', $records[0]['ordernumber']);
    }

    public function testMinimalCategoryCount(): void
    {
        $tree = $this->getMinimalCategoryTree();
        $count = $this->getReaderCount($tree, 'CategoriesImport.xml');

        static::assertEquals(16, $count);
    }

    public function testMinimalCategoriesRead(): void
    {
        $tree = $this->getMinimalCategoryTree();
        $records = $this->getReaderRecords($tree, 'CategoriesImport.xml', 5, 8);

        static::assertCount(8, $records);

        static::assertEquals(1009, $records[0]['categoryId']);
        static::assertEquals(1005, $records[0]['parentID']);
        static::assertEquals('SubCategory2', $records[0]['description']);

        $lastRecord = \end($records);
        static::assertIsArray($lastRecord);
        static::assertEquals(1016, $lastRecord['categoryId']);
        static::assertEquals(1013, $lastRecord['parentID']);
        static::assertEquals('Sub-Category1', $lastRecord['description']);
    }

    public function testMinimalCustomerCount(): void
    {
        $tree = $this->getMinimalCustomerTree();
        $count = $this->getReaderCount($tree, 'CustomerImport.xml');

        static::assertEquals(4, $count);
    }

    public function testMinimalCustomerRead(): void
    {
        $tree = $this->getMinimalCustomerTree();
        $records = $this->getReaderRecords($tree, 'CustomerImport.xml');

        static::assertCount(4, $records);

        static::assertEquals('$2y$10$TK5lWW/5kSMUXg.yZpkmr.RQf1rs/BJIeOzYFwWoPslSOxSKjZpru', $records[1]['password']);
        static::assertEquals('Examplecity', $records[1]['billing_city']);

        $lastRecord = \end($records);
        static::assertIsArray($lastRecord);
        static::assertEquals('120008', $lastRecord['customernumber']);
        static::assertEquals('mf5 Password', $lastRecord['shipping_firstname']);
    }

    public function testProductTranslationCount(): void
    {
        $tree = $this->getProductTranslationTree();
        $count = $this->getReaderCount($tree, 'ArticleTranslationImport.xml');

        static::assertEquals(103, $count);
    }

    public function testProductTranslationRead(): void
    {
        $tree = $this->getProductTranslationTree();
        $records = $this->getReaderRecords($tree, 'ArticleTranslationImport.xml', 50, 53);

        static::assertCount(53, $records);

        static::assertEquals('SW10144', $records[0]['articlenumber']);
        static::assertEmpty($records['keywords']);

        $lastRecord = \end($records);
        static::assertIsArray($lastRecord);
        static::assertEquals('Shipping costs by weight', $lastRecord['name']);
    }

    public function testMinimalVariantsCount(): void
    {
        $tree = $this->getMinimalVariantsTree();
        $count = $this->getReaderCount($tree, 'VariantsMinimalImport.xml');

        static::assertEquals(3, $count);
    }

    public function testMinimalVariantsRead(): void
    {
        $tree = $this->getMinimalVariantsTree();
        $records = $this->getReaderRecords($tree, 'VariantsMinimalImport.xml');

        static::assertCount(3, $records);

        static::assertEquals('Farbe', $records[1]['configurator'][0]['configGroupName']);
        static::assertEquals('S', $records[1]['configurator'][1]['configOptionName']);
    }

    public function testOrdersCount(): void
    {
        $tree = $this->getOrdersTree();
        $count = $this->getReaderCount($tree, 'OrderImport.xml');

        static::assertEquals(17, $count);
    }

    public function testOrdersRead(): void
    {
        $tree = $this->getOrdersTree();
        $records = $this->getReaderRecords($tree, 'OrderImport.xml');

        static::assertCount(17, $records);

        static::assertEquals(57, $records[4]['orderId']);
        static::assertEmpty($records[4]['partnerId']);

        static::assertEquals('9a0271fe91e7fc853a4a7a1e7ca789c812257d74', $records[9]['temporaryId']);
        static::assertEquals('SW10145', $records[9]['details']['articleNumber']);

        $lastRecord = \end($records);
        static::assertIsArray($lastRecord);
        static::assertEquals(42.6, $lastRecord['invoiceAmount']);
        static::assertEquals(-4.3, $lastRecord['details']['price']);
    }

    public function testNewsletterRecipientsCount(): void
    {
        $tree = $this->getNewsletterRecipientsTree();
        $count = $this->getReaderCount($tree, 'NewsletterRecipientImport.xml');

        static::assertEquals(6, $count);
    }

    public function testNewsletterRecipientsRead(): void
    {
        $tree = $this->getNewsletterRecipientsTree();
        $records = $this->getReaderRecords($tree, 'NewsletterRecipientImport.xml', 3, 2);

        static::assertCount(2, $records);

        static::assertEquals('test4@exmaple.com', $records[0]['email']);

        $lastRecord = \end($records);
        static::assertIsArray($lastRecord);
        static::assertEquals('Test_Group', $lastRecord['group']);
    }

    /**
     * @param array<string, string|array> $tree
     */
    private function getReaderCount(array $tree, string $filename): int
    {
        $reader = new XmlFileReader();
        $reader->setTree($tree);

        return $reader->getTotalCount(self::IMPORT_FILES_DIR . $filename);
    }

    /**
     * @param array<string, string|array> $tree
     *
     * @return array<array>
     */
    private function getReaderRecords(array $tree, string $filename, int $startPosition = 0, int $count = 50): array
    {
        $reader = new XmlFileReader();
        $reader->setTree($tree);

        return $reader->readRecords(self::IMPORT_FILES_DIR . $filename, $startPosition, $count);
    }

    /**
     * @return array<string, string|array>
     */
    private function getDefaultProductTree(): array
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
                                6 => [
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
                                            ],
                                            'attributes' => null,
                                        ],
                                    ],
                                ],
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
                                    'id' => '541af887a00ee',
                                    'type' => 'leaf',
                                    'index' => 18,
                                    'name' => 'metatitle',
                                    'shopwareField' => 'metaTitle',
                                ],
                                19 => [
                                    'id' => '541af887a00ed',
                                    'type' => 'leaf',
                                    'index' => 19,
                                    'name' => 'keywords',
                                    'shopwareField' => 'keywords',
                                ],
                                20 => [
                                    'id' => '541af7f35d78a',
                                    'type' => 'leaf',
                                    'index' => 20,
                                    'name' => 'minpurchase',
                                    'shopwareField' => 'minPurchase',
                                ],
                                21 => [
                                    'id' => '541af889cfb71',
                                    'type' => 'leaf',
                                    'index' => 21,
                                    'name' => 'purchasesteps',
                                    'shopwareField' => 'purchaseSteps',
                                ],
                                22 => [
                                    'id' => '541af88c05567',
                                    'type' => 'leaf',
                                    'index' => 22,
                                    'name' => 'maxpurchase',
                                    'shopwareField' => 'maxPurchase',
                                ],
                                23 => [
                                    'id' => '541af88e24a40',
                                    'type' => 'leaf',
                                    'index' => 23,
                                    'name' => 'purchaseunit',
                                    'shopwareField' => 'purchaseUnit',
                                ],
                                24 => [
                                    'id' => '541af8907b3e3',
                                    'type' => 'leaf',
                                    'index' => 24,
                                    'name' => 'referenceunit',
                                    'shopwareField' => 'referenceUnit',
                                ],
                                25 => [
                                    'id' => '541af9dd95d11',
                                    'type' => 'leaf',
                                    'index' => 25,
                                    'name' => 'packunit',
                                    'shopwareField' => 'packUnit',
                                ],
                                26 => [
                                    'id' => '541af9e03ba80',
                                    'type' => 'leaf',
                                    'index' => 26,
                                    'name' => 'unitID',
                                    'shopwareField' => 'unitId',
                                ],
                                27 => [
                                    'id' => '541af9e2939b0',
                                    'type' => 'leaf',
                                    'index' => 27,
                                    'name' => 'pricegroupID',
                                    'shopwareField' => 'priceGroupId',
                                ],
                                28 => [
                                    'id' => '541af9e54b365',
                                    'type' => 'leaf',
                                    'index' => 28,
                                    'name' => 'pricegroupActive',
                                    'shopwareField' => 'priceGroupActive',
                                ],
                                29 => [
                                    'id' => '541afad534551',
                                    'type' => 'leaf',
                                    'index' => 29,
                                    'name' => 'laststock',
                                    'shopwareField' => 'lastStock',
                                ],
                                30 => [
                                    'id' => '541afad754eb9',
                                    'type' => 'leaf',
                                    'index' => 30,
                                    'name' => 'suppliernumber',
                                    'shopwareField' => 'supplierNumber',
                                ],
                                31 => [
                                    'id' => '540efb5f704bc',
                                    'type' => 'leaf',
                                    'index' => 31,
                                    'name' => 'purchaseprice',
                                    'shopwareField' => 'purchasePrice',
                                ],
                                32 => [
                                    'id' => '541afad9b7357',
                                    'type' => 'leaf',
                                    'index' => 32,
                                    'name' => 'weight',
                                    'shopwareField' => 'weight',
                                ],
                                33 => [
                                    'id' => '541afadc6536c',
                                    'type' => 'leaf',
                                    'index' => 33,
                                    'name' => 'width',
                                    'shopwareField' => 'width',
                                ],
                                34 => [
                                    'id' => '541afadfb5179',
                                    'type' => 'leaf',
                                    'index' => 34,
                                    'name' => 'height',
                                    'shopwareField' => 'height',
                                ],
                                35 => [
                                    'id' => '541afae631bc8',
                                    'type' => 'leaf',
                                    'index' => 35,
                                    'name' => 'length',
                                    'shopwareField' => 'length',
                                ],
                                36 => [
                                    'id' => '541afae97c6ec',
                                    'type' => 'leaf',
                                    'index' => 36,
                                    'name' => 'ean',
                                    'shopwareField' => 'ean',
                                ],
                                37 => [
                                    'id' => '53e0d5f7d03d4',
                                    'type' => '',
                                    'index' => 37,
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
                                                4 => [
                                                    'id' => '57c93872e082d',
                                                    'type' => 'leaf',
                                                    'index' => 4,
                                                    'name' => 'configSetName',
                                                    'shopwareField' => 'configSetName',
                                                    'defaultValue' => '',
                                                ],
                                            ],
                                            'attributes' => null,
                                        ],
                                    ],
                                ],
                                38 => [
                                    'id' => '54211df500e93',
                                    'name' => 'category',
                                    'index' => 38,
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
                                ],
                                78 => [
                                    'id' => '541afdba8e926',
                                    'name' => 'similars',
                                    'index' => 37,
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
     * @return array<string, string|array>
     */
    private function getMinimalProductTree(): array
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
                                    'id' => '53e0d3fea6646',
                                    'type' => 'leaf',
                                    'index' => 3,
                                    'name' => 'supplier',
                                    'shopwareField' => 'supplierName',
                                ],
                                4 => [
                                    'id' => '53e0d4333dca7',
                                    'type' => 'leaf',
                                    'index' => 4,
                                    'name' => 'tax',
                                    'shopwareField' => 'tax',
                                ],
                                5 => [
                                    'id' => '53e0d44938a70',
                                    'type' => 'node',
                                    'index' => 5,
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
                                            ],
                                            'attributes' => null,
                                        ],
                                    ],
                                ],
                                6 => [
                                    'id' => '53fb272db680f',
                                    'type' => 'leaf',
                                    'index' => 6,
                                    'name' => 'active',
                                    'shopwareField' => 'active',
                                ],
                                7 => [
                                    'id' => '54211df500e93',
                                    'name' => 'category',
                                    'index' => 7,
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
     * @return array<string, string|array>
     */
    private function getMinimalCategoryTree(): array
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
                    'name' => 'categories',
                    'index' => 1,
                    'type' => '',
                    'children' => [
                        0 => [
                            'id' => '55f9329b5e4ac',
                            'name' => 'category',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => [
                                0 => [
                                    'id' => '55f932a2b718c',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'categoryId',
                                    'shopwareField' => 'categoryId',
                                ],
                                1 => [
                                    'id' => '55f933ec6308f',
                                    'type' => 'leaf',
                                    'index' => 1,
                                    'name' => 'parentId',
                                    'shopwareField' => 'parentId',
                                ],
                                2 => [
                                    'id' => '55f9339f41adb',
                                    'type' => 'leaf',
                                    'index' => 2,
                                    'name' => 'name',
                                    'shopwareField' => 'name',
                                ],
                            ],
                        ],
                    ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string|array>
     */
    private function getMinimalCustomerTree(): array
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
                    'name' => 'customers',
                    'index' => 1,
                    'type' => '',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '53ea047e7dca5',
                            'name' => 'customer',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'children' => [
                                0 => [
                                    'id' => '53ea048def53f',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'customernumber',
                                    'shopwareField' => 'customerNumber',
                                ],
                                1 => [
                                    'id' => '53ea052c8f4c9',
                                    'type' => 'leaf',
                                    'index' => 1,
                                    'name' => 'email',
                                    'shopwareField' => 'email',
                                ],
                                2 => [
                                    'id' => '53ea0535e3348',
                                    'type' => 'leaf',
                                    'index' => 2,
                                    'name' => 'password',
                                    'shopwareField' => 'password',
                                ],
                                3 => [
                                    'id' => '53fb366466188',
                                    'type' => 'leaf',
                                    'index' => 3,
                                    'name' => 'encoder',
                                    'shopwareField' => 'encoder',
                                ],
                                4 => [
                                    'id' => '53ea054339f8e',
                                    'type' => 'leaf',
                                    'index' => 4,
                                    'name' => 'billing_company',
                                    'shopwareField' => 'billingCompany',
                                    'defaultValue' => '',
                                ],
                                5 => [
                                    'id' => '53ea057725a7d',
                                    'type' => 'leaf',
                                    'index' => 5,
                                    'name' => 'billing_department',
                                    'shopwareField' => 'billingDepartment',
                                    'defaultValue' => '',
                                ],
                                6 => [
                                    'id' => '53ea0595b1d31',
                                    'type' => 'leaf',
                                    'index' => 6,
                                    'name' => 'billing_salutation',
                                    'shopwareField' => 'billingSalutation',
                                    'defaultValue' => '',
                                ],
                                7 => [
                                    'id' => '53ea05dba6a4d',
                                    'type' => 'leaf',
                                    'index' => 7,
                                    'name' => 'billing_firstname',
                                    'shopwareField' => 'billingFirstname',
                                    'defaultValue' => '',
                                ],
                                8 => [
                                    'id' => '53ea05de1204b',
                                    'type' => 'leaf',
                                    'index' => 8,
                                    'name' => 'billing_lastname',
                                    'shopwareField' => 'billingLastname',
                                    'defaultValue' => '',
                                ],
                                9 => [
                                    'id' => '53ea05df9caf1',
                                    'type' => 'leaf',
                                    'index' => 9,
                                    'name' => 'billing_street',
                                    'shopwareField' => 'billingStreet',
                                    'defaultValue' => '',
                                ],
                                10 => [
                                    'id' => '53ea05e271edd',
                                    'type' => 'leaf',
                                    'index' => 10,
                                    'name' => 'billing_zipcode',
                                    'shopwareField' => 'billingZipcode',
                                    'defaultValue' => '',
                                ],
                                11 => [
                                    'id' => '53ea05e417656',
                                    'type' => 'leaf',
                                    'index' => 11,
                                    'name' => 'billing_city',
                                    'shopwareField' => 'billingCity',
                                    'defaultValue' => '',
                                ],
                                12 => [
                                    'id' => '53ea0652597f1',
                                    'type' => 'leaf',
                                    'index' => 12,
                                    'name' => 'billing_countryID',
                                    'shopwareField' => 'billingCountryID',
                                    'defaultValue' => '',
                                ],
                                13 => [
                                    'id' => '53ea0691b1774',
                                    'type' => 'leaf',
                                    'index' => 13,
                                    'name' => 'ustid',
                                    'shopwareField' => 'ustid',
                                    'defaultValue' => '',
                                ],
                                14 => [
                                    'id' => '53ea0e5c6d67e',
                                    'type' => 'leaf',
                                    'index' => 14,
                                    'name' => 'paymentID',
                                    'shopwareField' => 'paymentID',
                                    'defaultValue' => 0,
                                ],
                                15 => [
                                    'id' => '53ea118664a90',
                                    'type' => 'leaf',
                                    'index' => 15,
                                    'name' => 'customergroup',
                                    'shopwareField' => 'customergroup',
                                    'defaultValue' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string|array>
     */
    private function getProductTranslationTree(): array
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
                            'children' => [
                                0 => [
                                    'id' => '5429676d78b28',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'articlenumber',
                                    'shopwareField' => 'articleNumber',
                                ],
                                1 => [
                                    'id' => '543798726b38e',
                                    'type' => 'leaf',
                                    'index' => 1,
                                    'name' => 'languageId',
                                    'shopwareField' => 'languageId',
                                ],
                                2 => [
                                    'id' => '53ce5e8f25a24',
                                    'name' => 'name',
                                    'index' => 2,
                                    'type' => 'leaf',
                                    'shopwareField' => 'name',
                                ],
                                3 => [
                                    'id' => '53ce5f9501db7',
                                    'name' => 'description',
                                    'index' => 3,
                                    'type' => 'leaf',
                                    'shopwareField' => 'description',
                                ],
                                4 => [
                                    'id' => '53ce5fa3bd231',
                                    'name' => 'longdescription',
                                    'index' => 4,
                                    'type' => 'leaf',
                                    'shopwareField' => 'descriptionLong',
                                ],
                                5 => [
                                    'id' => '53ce5fb6d95d8',
                                    'name' => 'keywords',
                                    'index' => 5,
                                    'type' => 'leaf',
                                    'shopwareField' => 'keywords',
                                ],
                                6 => [
                                    'id' => '542a5df925af2',
                                    'type' => 'leaf',
                                    'index' => 6,
                                    'name' => 'metatitle',
                                    'shopwareField' => 'metaTitle',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string|array>
     */
    private function getMinimalVariantsTree(): array
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
                                    'id' => '53e0d3fea6646',
                                    'type' => 'leaf',
                                    'index' => 3,
                                    'name' => 'supplier',
                                    'shopwareField' => 'supplierName',
                                ],
                                4 => [
                                    'id' => '53e0d4333dca7',
                                    'type' => 'leaf',
                                    'index' => 4,
                                    'name' => 'tax',
                                    'shopwareField' => 'tax',
                                ],
                                5 => [
                                    'id' => '57a49838a7656',
                                    'type' => 'leaf',
                                    'index' => 5,
                                    'name' => 'kind',
                                    'shopwareField' => 'kind',
                                    'defaultValue' => '',
                                ],
                                6 => [
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
                                            ],
                                            'attributes' => null,
                                        ],
                                    ],
                                    'defaultValue' => '',
                                ],
                                7 => [
                                    'id' => '53fb272db680f',
                                    'type' => 'leaf',
                                    'index' => 7,
                                    'name' => 'active',
                                    'shopwareField' => 'active',
                                    'defaultValue' => 0,
                                ],
                                8 => [
                                    'id' => '54211df500e93',
                                    'name' => 'category',
                                    'index' => 8,
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
                                    'defaultValue' => '',
                                ],
                                9 => [
                                    'id' => '55d59e2fc0c56',
                                    'name' => 'configurator',
                                    'index' => 9,
                                    'type' => 'iteration',
                                    'adapter' => 'configurator',
                                    'parentKey' => 'variantId',
                                    'shopwareField' => '',
                                    'children' => [
                                        0 => [
                                            'id' => '55d59e3d21483',
                                            'type' => 'leaf',
                                            'index' => 0,
                                            'name' => 'configGroupName',
                                            'shopwareField' => 'configGroupName',
                                        ],
                                        1 => [
                                            'id' => '55d59e4b02a39',
                                            'type' => 'leaf',
                                            'index' => 1,
                                            'name' => 'configOptionName',
                                            'shopwareField' => 'configOptionName',
                                        ],
                                    ],
                                    'defaultValue' => '',
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
     * @return array<string, string|array>
     */
    private function getOrdersTree(): array
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
                    'name' => 'orders',
                    'index' => 1,
                    'type' => 'node',
                    'children' => [
                        0 => [
                            'id' => '537359399c90d',
                            'name' => 'order',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'attributes' => null,
                            'children' => [
                                0 => [
                                    'id' => '53eca77b49d6d',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'orderId',
                                    'shopwareField' => 'orderId',
                                ],
                                1 => [
                                    'id' => '5373865547d06',
                                    'name' => 'number',
                                    'index' => 1,
                                    'type' => 'leaf',
                                    'shopwareField' => 'number',
                                ],
                                2 => [
                                    'id' => '53ecb1fa09cfd',
                                    'type' => 'leaf',
                                    'index' => 2,
                                    'name' => 'customerId',
                                    'shopwareField' => 'customerId',
                                ],
                                3 => [
                                    'id' => '53ecb3a3e43fb',
                                    'type' => 'leaf',
                                    'index' => 3,
                                    'name' => 'orderStatusID',
                                    'shopwareField' => 'status',
                                ],
                                4 => [
                                    'id' => '53ecb496e80e0',
                                    'type' => 'leaf',
                                    'index' => 4,
                                    'name' => 'cleared',
                                    'shopwareField' => 'cleared',
                                ],
                                5 => [
                                    'id' => '53ecb4e584159',
                                    'type' => 'leaf',
                                    'index' => 5,
                                    'name' => 'paymentID',
                                    'shopwareField' => 'paymentId',
                                ],
                                6 => [
                                    'id' => '53ecb4f9a203b',
                                    'type' => 'leaf',
                                    'index' => 6,
                                    'name' => 'dispatchId',
                                    'shopwareField' => 'dispatchId',
                                ],
                                7 => [
                                    'id' => '53ecb510a3379',
                                    'type' => 'leaf',
                                    'index' => 7,
                                    'name' => 'partnerId',
                                    'shopwareField' => 'partnerId',
                                ],
                                8 => [
                                    'id' => '53ecb51a93f21',
                                    'type' => 'leaf',
                                    'index' => 8,
                                    'name' => 'shopId',
                                    'shopwareField' => 'shopId',
                                ],
                                9 => [
                                    'id' => '53ecb6a059334',
                                    'type' => 'leaf',
                                    'index' => 9,
                                    'name' => 'invoiceAmount',
                                    'shopwareField' => 'invoiceAmount',
                                ],
                                10 => [
                                    'id' => '53ecb6a74e399',
                                    'type' => 'leaf',
                                    'index' => 10,
                                    'name' => 'invoiceAmountNet',
                                    'shopwareField' => 'invoiceAmountNet',
                                ],
                                11 => [
                                    'id' => '53ecb6b4587ba',
                                    'type' => 'leaf',
                                    'index' => 11,
                                    'name' => 'invoiceShipping',
                                    'shopwareField' => 'invoiceShipping',
                                ],
                                12 => [
                                    'id' => '53ecb6be27e2e',
                                    'type' => 'leaf',
                                    'index' => 12,
                                    'name' => 'invoiceShippingNet',
                                    'shopwareField' => 'invoiceShippingNet',
                                ],
                                13 => [
                                    'id' => '53ecb6db22a2e',
                                    'type' => 'leaf',
                                    'index' => 13,
                                    'name' => 'orderTime',
                                    'shopwareField' => 'orderTime',
                                ],
                                14 => [
                                    'id' => '53ecb6ebaf4c5',
                                    'type' => 'leaf',
                                    'index' => 14,
                                    'name' => 'transactionId',
                                    'shopwareField' => 'transactionId',
                                ],
                                15 => [
                                    'id' => '53ecb7014e7ad',
                                    'type' => 'leaf',
                                    'index' => 15,
                                    'name' => 'comment',
                                    'shopwareField' => 'comment',
                                ],
                                16 => [
                                    'id' => '53ecb7f0df5db',
                                    'type' => 'leaf',
                                    'index' => 16,
                                    'name' => 'customerComment',
                                    'shopwareField' => 'customerComment',
                                ],
                                17 => [
                                    'id' => '53ecb7f265873',
                                    'type' => 'leaf',
                                    'index' => 17,
                                    'name' => 'internalComment',
                                    'shopwareField' => 'internalComment',
                                ],
                                18 => [
                                    'id' => '53ecb7f3baed3',
                                    'type' => 'leaf',
                                    'index' => 18,
                                    'name' => 'net',
                                    'shopwareField' => 'net',
                                ],
                                19 => [
                                    'id' => '53ecb7f518b2a',
                                    'type' => 'leaf',
                                    'index' => 19,
                                    'name' => 'taxFree',
                                    'shopwareField' => 'taxFree',
                                ],
                                20 => [
                                    'id' => '53ecb7f778bb0',
                                    'type' => 'leaf',
                                    'index' => 20,
                                    'name' => 'temporaryId',
                                    'shopwareField' => 'temporaryId',
                                ],
                                21 => [
                                    'id' => '53ecb7f995899',
                                    'type' => 'leaf',
                                    'index' => 21,
                                    'name' => 'referer',
                                    'shopwareField' => 'referer',
                                ],
                                22 => [
                                    'id' => '53ecb8ba28544',
                                    'type' => 'leaf',
                                    'index' => 22,
                                    'name' => 'clearedDate',
                                    'shopwareField' => 'clearedDate',
                                ],
                                23 => [
                                    'id' => '53ecb8bd55dda',
                                    'type' => 'leaf',
                                    'index' => 23,
                                    'name' => 'trackingCode',
                                    'shopwareField' => 'trackingCode',
                                ],
                                24 => [
                                    'id' => '53ecb8c076318',
                                    'type' => 'leaf',
                                    'index' => 24,
                                    'name' => 'languageIso',
                                    'shopwareField' => 'languageIso',
                                ],
                                25 => [
                                    'id' => '53ecb8c42923d',
                                    'type' => 'leaf',
                                    'index' => 25,
                                    'name' => 'currency',
                                    'shopwareField' => 'currency',
                                ],
                                26 => [
                                    'id' => '53ecb8c74168b',
                                    'type' => 'leaf',
                                    'index' => 26,
                                    'name' => 'currencyFactor',
                                    'shopwareField' => 'currencyFactor',
                                ],
                                27 => [
                                    'id' => '53ecb9203cb33',
                                    'type' => 'leaf',
                                    'index' => 27,
                                    'name' => 'remoteAddress',
                                    'shopwareField' => 'remoteAddress',
                                ],
                                28 => [
                                    'id' => '53fddf437e561',
                                    'type' => 'node',
                                    'index' => 28,
                                    'name' => 'details',
                                    'shopwareField' => '',
                                    'children' => [
                                        0 => [
                                            'id' => '53ecb9c7d602d',
                                            'type' => 'leaf',
                                            'index' => 0,
                                            'name' => 'orderDetailId',
                                            'shopwareField' => 'orderDetailId',
                                        ],
                                        1 => [
                                            'id' => '53ecb9ee6f821',
                                            'type' => 'leaf',
                                            'index' => 1,
                                            'name' => 'articleId',
                                            'shopwareField' => 'articleId',
                                        ],
                                        2 => [
                                            'id' => '53ecbaa627334',
                                            'type' => 'leaf',
                                            'index' => 2,
                                            'name' => 'taxId',
                                            'shopwareField' => 'taxId',
                                        ],
                                        3 => [
                                            'id' => '53ecba416356a',
                                            'type' => 'leaf',
                                            'index' => 3,
                                            'name' => 'taxRate',
                                            'shopwareField' => 'taxRate',
                                        ],
                                        4 => [
                                            'id' => '53ecbaa813093',
                                            'type' => 'leaf',
                                            'index' => 4,
                                            'name' => 'statusId',
                                            'shopwareField' => 'statusId',
                                        ],
                                        5 => [
                                            'id' => '53ecbb05eccf1',
                                            'type' => 'leaf',
                                            'index' => 5,
                                            'name' => 'number',
                                            'shopwareField' => 'number',
                                        ],
                                        6 => [
                                            'id' => '53ecbb0411d43',
                                            'type' => 'leaf',
                                            'index' => 6,
                                            'name' => 'articleNumber',
                                            'shopwareField' => 'articleNumber',
                                        ],
                                        7 => [
                                            'id' => '53ecba19dc9ef',
                                            'type' => 'leaf',
                                            'index' => 7,
                                            'name' => 'price',
                                            'shopwareField' => 'price',
                                        ],
                                        8 => [
                                            'id' => '53ecba29e1a37',
                                            'type' => 'leaf',
                                            'index' => 8,
                                            'name' => 'quantity',
                                            'shopwareField' => 'quantity',
                                        ],
                                        9 => [
                                            'id' => '53ecba34bf110',
                                            'type' => 'leaf',
                                            'index' => 9,
                                            'name' => 'articleName',
                                            'shopwareField' => 'articleName',
                                        ],
                                        10 => [
                                            'id' => '53ecbb07dda54',
                                            'type' => 'leaf',
                                            'index' => 10,
                                            'name' => 'shipped',
                                            'shopwareField' => 'shipped',
                                        ],
                                        11 => [
                                            'id' => '53ecbb09bb007',
                                            'type' => 'leaf',
                                            'index' => 11,
                                            'name' => 'shippedGroup',
                                            'shopwareField' => 'shippedGroup',
                                        ],
                                        12 => [
                                            'id' => '53ecbbc15479a',
                                            'type' => 'leaf',
                                            'index' => 12,
                                            'name' => 'releaseDate',
                                            'shopwareField' => 'releasedate',
                                        ],
                                        13 => [
                                            'id' => '53ecbbc40bcd3',
                                            'type' => 'leaf',
                                            'index' => 13,
                                            'name' => 'mode',
                                            'shopwareField' => 'mode',
                                        ],
                                        14 => [
                                            'id' => '53ecbbc57169d',
                                            'type' => 'leaf',
                                            'index' => 14,
                                            'name' => 'esdArticle',
                                            'shopwareField' => 'esd',
                                        ],
                                        15 => [
                                            'id' => '53ecbbc6b6f2c',
                                            'type' => 'leaf',
                                            'index' => 15,
                                            'name' => 'config',
                                            'shopwareField' => 'config',
                                        ],
                                    ],
                                ],
                            ],
                            'shopwareField' => '',
                            'parentKey' => '',
                        ],
                    ],
                    'shopwareField' => '',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string|array>
     */
    private function getNewsletterRecipientsTree(): array
    {
        return [
            'id' => 'root',
            'name' => 'Root',
            'type' => 'node',
            'children' => [
                1 => [
                    'id' => '537359399c8b7',
                    'name' => 'Users',
                    'index' => 0,
                    'type' => 'node',
                    'shopwareField' => '',
                    'children' => [
                        0 => [
                            'id' => '537359399c90d',
                            'name' => 'user',
                            'index' => 0,
                            'type' => 'iteration',
                            'adapter' => 'default',
                            'parentKey' => '',
                            'shopwareField' => '',
                            'attributes' => [
                            ],
                            'children' => [
                                0 => [
                                    'id' => '53e4b0f86aded',
                                    'type' => 'leaf',
                                    'index' => 0,
                                    'name' => 'email',
                                    'shopwareField' => 'email',
                                ],
                                1 => [
                                    'id' => '53e4b103bf001',
                                    'type' => 'leaf',
                                    'index' => 1,
                                    'name' => 'group',
                                    'shopwareField' => 'groupName',
                                ],
                                2 => [
                                    'id' => '53e4b105ea8c2',
                                    'type' => 'leaf',
                                    'index' => 2,
                                    'name' => 'salutation',
                                    'shopwareField' => 'salutation',
                                ],
                                3 => [
                                    'id' => '53e4b107872be',
                                    'type' => 'leaf',
                                    'index' => 3,
                                    'name' => 'firstname',
                                    'shopwareField' => 'firstName',
                                ],
                                4 => [
                                    'id' => '53e4b108d49f9',
                                    'type' => 'leaf',
                                    'index' => 4,
                                    'name' => 'lastname',
                                    'shopwareField' => 'lastName',
                                ],
                                5 => [
                                    'id' => '53e4b10a38e08',
                                    'type' => 'leaf',
                                    'index' => 5,
                                    'name' => 'street',
                                    'shopwareField' => 'street',
                                ],
                                6 => [
                                    'id' => '53e4b10d68c09',
                                    'type' => 'leaf',
                                    'index' => 7,
                                    'name' => 'zipcode',
                                    'shopwareField' => 'zipCode',
                                ],
                                7 => [
                                    'id' => '53e4b157416fc',
                                    'type' => 'leaf',
                                    'index' => 8,
                                    'name' => 'city',
                                    'shopwareField' => 'city',
                                ],
                                8 => [
                                    'id' => '53e4b1592dd4b',
                                    'type' => 'leaf',
                                    'index' => 9,
                                    'name' => 'lastmailing',
                                    'shopwareField' => 'lastNewsletter',
                                ],
                                9 => [
                                    'id' => '53e4b15a69651',
                                    'type' => 'leaf',
                                    'index' => 10,
                                    'name' => 'lastread',
                                    'shopwareField' => 'lastRead',
                                ],
                                10 => [
                                    'id' => '53e4b15bde918',
                                    'type' => 'leaf',
                                    'index' => 11,
                                    'name' => 'userID',
                                    'shopwareField' => 'userID',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
