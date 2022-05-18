<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class ArticlesImagesDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty(): void
    {
        $productsImagesDbAdapter = $this->getProductImagesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine neuen Artikelbilder gefunden.');
        $productsImagesDbAdapter->write([]);
    }

    public function testWriteShouldThrowExceptionHavingWrongPath(): void
    {
        $productsImagesDbAdapter = $this->getProductImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => '/../../../image.png',
                    'description' => 'testimport1',
                    'thumbnail' => 1,
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nicht-unterstütztes Schema "No URL scheme given".');
        $productsImagesDbAdapter->write($records);
    }

    public function testNewProductImageShouldBeWrittenToDatabase(): void
    {
        $productsImagesDbAdapter = $this->getProductImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => $this->getImportImagePath(),
                    'description' => 'testimport1',
                    'thumbnail' => 1,
                ],
            ],
        ];
        $productsImagesDbAdapter->write($records);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10001'")->fetch(\PDO::FETCH_COLUMN);
        $image = $dbalConnection->executeQuery("SELECT * FROM s_articles_img WHERE description = 'testimport1'")->fetch(\PDO::FETCH_ASSOC);

        static::assertSame($records['default'][0]['description'], $image['description']);
        static::assertSame($productId, $image['articleID']);
        static::assertSame('png', $image['extension']);
    }

    public function testNewProductImageFromHTTPShouldBeWrittenToDatabase(): void
    {
        $productsImagesDbAdapter = $this->getProductImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => 'https://assets.shopware.com/media/logos/shopware_logo_blue.svg',
                    'description' => 'testimport1',
                    'thumbnail' => 1,
                ],
            ],
        ];
        $productsImagesDbAdapter->write($records);

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productId = $dbalConnection->executeQuery("SELECT articleID FROM s_articles_details WHERE orderNumber='SW10001'")->fetch(\PDO::FETCH_COLUMN);
        $image = $dbalConnection->executeQuery("SELECT * FROM s_articles_img WHERE description = 'testimport1'")->fetch(\PDO::FETCH_ASSOC);

        static::assertSame($records['default'][0]['description'], $image['description']);
        static::assertSame($productId, $image['articleID']);
        static::assertSame('svg', $image['extension']);
    }

    public function testWriteWithInvalidOrderNumberThrowsException(): void
    {
        $productsImagesDbAdapter = $this->getProductImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'invalid-order-number',
                    'image' => $this->getImportImagePath(),
                    'description' => 'testimport1',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Artikel mit Nummer invalid-order-number existiert nicht.');
        $productsImagesDbAdapter->write($records);
    }

    public function testWriteWithNotExistingImageThrowsException(): void
    {
        $productsImagesDbAdapter = $this->getProductImagesDbAdapter();
        $records = [
            'default' => [
                [
                    'ordernumber' => 'SW10001',
                    'image' => $this->getInvalidImportImagePath(),
                    'description' => 'testimport1',
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kann file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/invalid_image_name.png nicht zum Lesen öffnen');
        $productsImagesDbAdapter->write($records);
    }

    private function getProductImagesDbAdapter(): ArticlesImagesDbAdapter
    {
        return $this->getContainer()->get(ArticlesImagesDbAdapter::class);
    }

    private function getImportImagePath(): string
    {
        return 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/sw-icon_blue128.png';
    }

    private function getInvalidImportImagePath(): string
    {
        return 'file://' . \realpath(__DIR__) . '/../../../../Helper/ImportFiles/invalid_image_name.png';
    }
}
