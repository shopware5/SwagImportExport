<?php

declare(strict_types=1);

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Products;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Supplier;
use SwagImportExport\Components\DbAdapters\Products\ProductWriter;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Tests\Helper\ContainerTrait;

class ProductWriterTest extends TestCase
{
    use ContainerTrait;

    private ProductWriter $productWriter;

    private ModelManager $modelManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelManager = $this->getContainer()->get('models');
        $this->modelManager->beginTransaction();

        $this->productWriter = $this->getContainer()->get(ProductWriter::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->modelManager->rollback();
    }

    public function testWrite(): void
    {
        $expectedProductId = 3;
        $expectedDetailId = 3;
        $expectedMainDetailId = 3;

        $demoProduct = [
            'orderNumber' => 'SW10003',
            'mainNumber' => 'SW10003',
            'additionalText' => '',
            'supplierName' => 'Feinbrennerei Sasse',
            'tax' => 19.00,
            'active' => 1,
            'inStock' => 25,
            'stockMin' => 0,
            'description' => 'This is the description of a very good drink.',
            'descriptionLong' => 'This is the description of a very good drink. This description is longeeeer.',
            'unitId' => 1,
        ];

        $productWriterResult = $this->productWriter->write($demoProduct, []);

        static::assertEquals($expectedProductId, $productWriterResult->getProductId(), 'Expected articleId does not match the obtained articleId.');
        static::assertEquals($expectedDetailId, $productWriterResult->getDetailId(), 'Expected detailId does not match the obtained detailId.');
        static::assertEquals($expectedMainDetailId, $productWriterResult->getMainDetailId(), 'Expected mainDetailId id does not match the obtained detailId.');
    }

    public function testWriteShouldInsertANewProduct(): void
    {
        $expectedNewProduct = [
            'orderNumber' => 'test-9999',
            'mainNumber' => 'test-9999',
            'name' => 'Sleep pill',
            'additionalText' => '',
            'supplierName' => 'Test Supplier',
            'tax' => '19.00',
            'active' => '1',
            'inStock' => '25',
            'stockMin' => '0',
            'description' => 'This is the description of a very good product.',
            'descriptionLong' => "This product gives you the best abilities to sleep. Don't try it at work, you will get fired!",
            'unitId' => '1',
        ];

        $productWriterResult = $this->productWriter->write($expectedNewProduct, []);

        $insertedProduct = $this->modelManager->find(Article::class, $productWriterResult->getProductId());
        static::assertInstanceOf(Article::class, $insertedProduct);

        $mainDetail = $insertedProduct->getMainDetail();
        static::assertInstanceOf(Detail::class, $mainDetail);

        $supplier = $insertedProduct->getSupplier();
        static::assertInstanceOf(Supplier::class, $supplier);

        static::assertNotNull($insertedProduct, 'Could not insert article');
        static::assertEquals($expectedNewProduct['orderNumber'], $mainDetail->getNumber(), 'Could not insert field ordernumber.');
        static::assertEquals($expectedNewProduct['description'], $insertedProduct->getDescription(), 'Could not insert field description.');
        static::assertEquals($expectedNewProduct['descriptionLong'], $insertedProduct->getDescriptionLong(), 'Could not insert field description_long.');
        static::assertEquals($expectedNewProduct['inStock'], $mainDetail->getInStock(), 'Could not insert field instock.');
        static::assertEquals($expectedNewProduct['active'], $insertedProduct->getActive(), 'Could not insert field active.');
        static::assertEquals($expectedNewProduct['supplierName'], $supplier->getName(), 'Could not insert field supplier name.');
    }

    public function testWriteShouldUpdateAnExistingProduct(): void
    {
        $expectedModifiedProduct = [
            'orderNumber' => 'SW10002',
            'mainNumber' => 'SW10002',
            'additionalText' => '',
            'supplierName' => 'Rolinck',
            'name' => 'Beer',
            'tax' => '19.00',
            'active' => '0',
            'lastStock' => '1',
            'inStock' => '45',
            'stockMin' => '0',
            'description' => 'This is the description of a very good drink. The description should be updated.',
            'descriptionLong' => 'This is the description of a very good drink. This description is longeeeer. And should be updated!',
            'unitId' => '1',
            'kind' => 2,
        ];

        $productWriterResult = $this->productWriter->write($expectedModifiedProduct, []);

        $updatedProduct = $this->modelManager->find(Article::class, $productWriterResult->getProductId());
        static::assertInstanceOf(Article::class, $updatedProduct);

        $mainDetail = $updatedProduct->getMainDetail();
        static::assertInstanceOf(Detail::class, $mainDetail);

        $supplier = $updatedProduct->getSupplier();
        static::assertInstanceOf(Supplier::class, $supplier);

        static::assertNotNull($updatedProduct, 'Could not find updated article');
        static::assertEquals($expectedModifiedProduct['kind'], $mainDetail->getKind(), 'Could not update kind.');
        static::assertEquals($expectedModifiedProduct['orderNumber'], $mainDetail->getNumber(), 'Could not update field ordernumber.');
        static::assertEquals($expectedModifiedProduct['description'], $updatedProduct->getDescription(), 'Could not update field description.');
        static::assertEquals($expectedModifiedProduct['descriptionLong'], $updatedProduct->getDescriptionLong(), 'Could not update field description long.');
        static::assertEquals($expectedModifiedProduct['inStock'], $mainDetail->getInStock(), 'Could not update field instock.');
        static::assertEquals($expectedModifiedProduct['lastStock'], $mainDetail->getLastStock(), 'Could not update field last stock.');
        static::assertFalse($updatedProduct->getActive(), 'Could not update field active.');
        static::assertEquals($expectedModifiedProduct['supplierName'], $supplier->getName(), 'Could not update field supplier name.');
    }

    public function testWriteWithProcessedProduct(): void
    {
        $expectedId = 3;
        $expectedDetailId = 3;
        $expectedMainDetailId = 3;

        $expectedModifiedProduct = [
            'orderNumber' => 'SW10003',
            'mainNumber' => 'SW10003',
            'processed' => '1',
        ];

        $productWriterResult = $this->productWriter->write($expectedModifiedProduct, []);

        static::assertEquals($productWriterResult->getProductId(), $expectedId, 'The expected article id do not match');
        static::assertEquals($productWriterResult->getDetailId(), $expectedDetailId, 'The expected article detail id do not match');
        static::assertEquals($productWriterResult->getMainDetailId(), $expectedMainDetailId, 'The expected article main detail id do not match');
    }

    public function testWriteDetailWithNotExistingMainDetailShouldThrowException(): void
    {
        $expectedModifiedProduct = [
            'orderNumber' => 'number_does_not_exist',
            'mainNumber' => 'number_does_not_exist_and_is_different',
        ];

        $this->expectException(AdapterException::class);
        $this->productWriter->write($expectedModifiedProduct, []);
    }

    public function testWriteShouldUpdateProductActiveFlagIfMainDetailActiveFlagIsGiven(): void
    {
        $expectedModifiedProduct = [
            'orderNumber' => 'SW10123.1',
            'mainNumber' => 'SW10123.1',
            'active' => '0',
        ];

        $productWriterResult = $this->productWriter->write($expectedModifiedProduct, []);

        $isMainProductActive = $this->getProductsActiveFlag($productWriterResult->getProductId());
        $isMainDetailActive = $this->getProductDetailActiveFlag($productWriterResult->getDetailId());

        static::assertFalse($isMainDetailActive, 'Could not update active flag for article main detail.');
        static::assertFalse($isMainProductActive, 'Could not update active flag for s_articles if main detail active flag is given.');
    }

    public function testWriteShouldNotUpdateProductActiveFlagIfDetailActiveFlagIsGiven(): void
    {
        $expectedModifiedProduct = [
            'orderNumber' => 'SW10123.2',
            'mainNumber' => 'SW10123.1',
            'active' => '0',
        ];

        $productWriterResult = $this->productWriter->write($expectedModifiedProduct, []);

        $isMainProductActive = $this->getProductsActiveFlag($productWriterResult->getProductId());
        $isDetailActive = $this->getProductDetailActiveFlag($productWriterResult->getDetailId());

        static::assertFalse($isDetailActive, 'Could not update article detail active flag.');
        static::assertTrue($isMainProductActive, 'Article active flag was updated, but only article detail should be updated.');
    }

    private function getProductsActiveFlag(int $productId): bool
    {
        $connection = $this->modelManager->getConnection();

        return (bool) $connection->executeQuery('SELECT active FROM s_articles WHERE id = ?', [$productId])->fetchOne();
    }

    private function getProductDetailActiveFlag(int $detailId): bool
    {
        $connection = $this->modelManager->getConnection();

        return (bool) $connection->executeQuery('SELECT active FROM s_articles_details WHERE id = ?', [$detailId])->fetchOne();
    }
}
