<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\Products\PriceWriter;
use SwagImportExport\Components\Utils\SwagVersionHelper;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class PriceWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteThrowsExceptionIfPriceGroupNotExists(): void
    {
        $priceWriterDbAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => '9,95',
                'priceGroup' => 'price_group_does_not_exist',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kundengruppe mit Schlüssel price_group_does_not_exist nicht gefunden für Artikel SW10003.');
        $priceWriterDbAdapter->write($productId, $productOrderNumber, $productPriceData);
    }

    public function testWriteThrowsExceptionIfPriceIsInvalid(): void
    {
        $priceWriterDbAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => 'invalidPrice',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('price Feld muss float sein und nicht invalidPrice!');
        $priceWriterDbAdapter->write($productId, $productOrderNumber, $productPriceData);
    }

    public function testWriteThrowsExceptionIfPriceFromIsInvalid(): void
    {
        $priceWriterDbAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => '9,95',
                'from' => '-12',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Ungültiger von Wert im Preis für Artikel SW10003.');
        $priceWriterDbAdapter->write($productId, $productOrderNumber, $productPriceData);
    }

    public function testWriteShouldUpdatePriceWithDotSeperation(): void
    {
        $priceWriterAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => '9.95',
                'priceGroup' => 'EK',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;
        $expectedProductPrice = 8.3613445378151;

        $priceWriterAdapter->write($productId, $productOrderNumber, $productPriceData);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedProduct = $dbalConnection->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$productId}'")->fetchAll();

        static::assertEquals($expectedProductPrice, $updatedProduct[0]['price']);
    }

    public function testWriteShouldUpdateProductPrice(): void
    {
        $priceWriterAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => '9,95',
                'priceGroup' => 'EK',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;
        $expectedProductPrice = 8.3613445378151;

        $priceWriterAdapter->write($productId, $productOrderNumber, $productPriceData);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedProduct = $dbalConnection->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$productId}'")->fetchAll();

        static::assertEquals($expectedProductPrice, $updatedProduct[0]['price']);
    }

    public function testWriteShouldUpdateProductPseudoPrice(): void
    {
        $priceWriterAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => '9,95',
                'priceGroup' => 'EK',
                'pseudoPrice' => '15,95',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;
        $expectedProductPseudoPrice = 13.403361344538;

        $priceWriterAdapter->write($productId, $productOrderNumber, $productPriceData);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedProduct = $dbalConnection->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$productId}'")->fetchAll();

        static::assertEquals($expectedProductPseudoPrice, $updatedProduct[0]['pseudoprice']);
    }

    public function testWriteShouldUpdateProductRegulationPrice(): void
    {
        if (!SwagVersionHelper::isShopware578()) {
            static::markTestSkipped('This test is not supported lower shopware version 5.7.8');
        }

        $priceWriterAdapter = $this->getPriceWriterAdapter();

        $productPriceData = [
            [
                'price' => '9,95',
                'priceGroup' => 'EK',
                'regulationPrice' => '15,95',
            ],
        ];
        $productOrderNumber = 3;
        $productId = 3;
        $expectedProductRegulationPrice = 13.403361344538;

        $priceWriterAdapter->write($productId, $productOrderNumber, $productPriceData);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedProduct = $dbalConnection->executeQuery("SELECT * FROM s_articles_prices WHERE articleID='{$productId}'")->fetchAll();

        static::assertEquals($expectedProductRegulationPrice, $updatedProduct[0]['regulation_price']);
    }

    private function getPriceWriterAdapter(): PriceWriter
    {
        return $this->getContainer()->get(PriceWriter::class);
    }
}
