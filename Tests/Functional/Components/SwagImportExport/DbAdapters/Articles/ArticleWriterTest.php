<?php

declare(strict_types=1);

/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\ArticleWriter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Article\Supplier;

class ArticleWriterTest extends TestCase
{
    /**
     * @var ArticleWriter
     */
    private $articleWriter;

    /**
     * @var ModelManager
     */
    private $modelManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->beginTransaction();

        $this->articleWriter = new ArticleWriter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->modelManager->rollback();
    }

    public function testWrite(): void
    {
        $expectedArticleId = 3;
        $expectedDetailId = 3;
        $expectedMainDetailId = 3;

        $demoArticle = [
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

        $articleWriterResult = $this->articleWriter->write($demoArticle, []);

        static::assertEquals($expectedArticleId, $articleWriterResult->getArticleId(), 'Expected articleId does not match the obtained articleId.');
        static::assertEquals($expectedDetailId, $articleWriterResult->getDetailId(), 'Expected detailId does not match the obtained detailId.');
        static::assertEquals($expectedMainDetailId, $articleWriterResult->getMainDetailId(), 'Expected mainDetailId id does not match the obtained detailId.');
    }

    public function testWriteShouldInsertANewArticle(): void
    {
        $expectedNewArticle = [
            'orderNumber' => 'test-9999',
            'mainNumber' => 'test-9999',
            'name' => 'Sleep pill',
            'additionalText' => '',
            'supplierName' => 'Test Supplier',
            'tax' => '19.00',
            'active' => '1',
            'inStock' => '25',
            'stockMin' => '0',
            'description' => 'This is the description of a very good product..',
            'descriptionLong' => 'This product gives you the best abilities to sleep. Dont try it at work, you will get fired!',
            'unitId' => '1',
        ];

        $articleWriterResult = $this->articleWriter->write($expectedNewArticle, []);

        /** @var Article $insertedArticle */
        $insertedArticle = $this->modelManager->find(Article::class, $articleWriterResult->getArticleId());

        $mainDetail = $insertedArticle->getMainDetail();
        static::assertInstanceOf(Detail::class, $mainDetail);

        $supplier = $insertedArticle->getSupplier();
        static::assertInstanceOf(Supplier::class, $supplier);

        static::assertNotNull($insertedArticle, 'Could not insert article');
        static::assertEquals($expectedNewArticle['orderNumber'], $mainDetail->getNumber(), 'Could not insert field ordernumber.');
        static::assertEquals($expectedNewArticle['description'], $insertedArticle->getDescription(), 'Could not insert field description.');
        static::assertEquals($expectedNewArticle['descriptionLong'], $insertedArticle->getDescriptionLong(), 'Could not insert field descrption_long.');
        static::assertEquals($expectedNewArticle['inStock'], $mainDetail->getInStock(), 'Could not insert field instock.');
        static::assertEquals($expectedNewArticle['active'], $insertedArticle->getActive(), 'Could not insert field active.');
        static::assertEquals($expectedNewArticle['supplierName'], $supplier->getName(), 'Could not insert field supplier name.');
    }

    public function testWriteShouldUpdateAnExistingArticle(): void
    {
        $expectedModifiedArticle = [
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

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        /** @var Article $updatedArticle */
        $updatedArticle = $this->modelManager->find(Article::class, $articleWriterResult->getArticleId());

        $mainDetail = $updatedArticle->getMainDetail();
        static::assertInstanceOf(Detail::class, $mainDetail);

        $supplier = $updatedArticle->getSupplier();
        static::assertInstanceOf(Supplier::class, $supplier);

        static::assertNotNull($updatedArticle, 'Could not find updated article');
        static::assertEquals($expectedModifiedArticle['kind'], $mainDetail->getKind(), 'Could not update kind.');
        static::assertEquals($expectedModifiedArticle['orderNumber'], $mainDetail->getNumber(), 'Could not update field ordernumber.');
        static::assertEquals($expectedModifiedArticle['description'], $updatedArticle->getDescription(), 'Could not update field description.');
        static::assertEquals($expectedModifiedArticle['descriptionLong'], $updatedArticle->getDescriptionLong(), 'Could not update field description long.');
        static::assertEquals($expectedModifiedArticle['inStock'], $mainDetail->getInStock(), 'Could not update field instock.');
        static::assertEquals($expectedModifiedArticle['lastStock'], $mainDetail->getLastStock(), 'Could not update field last stock.');
        static::assertFalse($updatedArticle->getActive(), 'Could not update field active.');
        static::assertEquals($expectedModifiedArticle['supplierName'], $supplier->getName(), 'Could not update field supplier name.');
    }

    public function testWriteWithProcessedArticle(): void
    {
        $expectedId = 3;
        $expectedDetailId = 3;
        $expectedMainDetailId = 3;

        $expectedModifiedArticle = [
            'orderNumber' => 'SW10003',
            'mainNumber' => 'SW10003',
            'processed' => '1',
        ];

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        static::assertEquals($articleWriterResult->getArticleId(), $expectedId, 'The expected article id do not match');
        static::assertEquals($articleWriterResult->getDetailId(), $expectedDetailId, 'The expected article detail id do not match');
        static::assertEquals($articleWriterResult->getMainDetailId(), $expectedMainDetailId, 'The expected article main detail id do not match');
    }

    public function testWriteDetailWithNotExistingMainDetailShouldThrowException(): void
    {
        $expectedModifiedArticle = [
            'orderNumber' => 'number_does_not_exist',
            'mainNumber' => 'number_does_not_exist_and_is_different',
        ];

        $this->expectException(AdapterException::class);
        $this->articleWriter->write($expectedModifiedArticle, []);
    }

    public function testWriteShouldUpdateArticleActiveFlagIfMainDetailActiveFlagIsGiven(): void
    {
        $expectedModifiedArticle = [
            'orderNumber' => 'SW10123.1',
            'mainNumber' => 'SW10123.1',
            'active' => '0',
        ];

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        $isMainArticleActive = $this->getArticlesActiveFlag($articleWriterResult->getArticleId());
        $isMainDetailActive = $this->getArticleDetailActiveFlag($articleWriterResult->getDetailId());

        static::assertEquals(0, $isMainDetailActive, 'Could not update active flag for article main detail.');
        static::assertEquals(0, $isMainArticleActive, 'Could not update active flag for s_articles if main detail active flag is given.');
    }

    public function testWriteShouldNotUpdateArticleActiveFlagIfDetailActiveFlagIsGiven(): void
    {
        $expectedModifiedArticle = [
            'orderNumber' => 'SW10123.2',
            'mainNumber' => 'SW10123.1',
            'active' => '0',
        ];

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        $isMainArticleActive = $this->getArticlesActiveFlag($articleWriterResult->getArticleId());
        $isDetailActive = $this->getArticleDetailActiveFlag($articleWriterResult->getDetailId());

        static::assertEquals(0, $isDetailActive, 'Could not update article detail active flag.');
        static::assertEquals(1, $isMainArticleActive, 'Article active flag was updated, but only article detail should be updated.');
    }

    protected function getArticlesActiveFlag(int $articleId): string
    {
        $connection = $this->modelManager->getConnection();

        $result = $connection->executeQuery('SELECT active FROM s_articles WHERE id = ?', [$articleId])->fetchColumn();

        static::assertIsString($result);

        return $result;
    }

    protected function getArticleDetailActiveFlag(int $detailId): string
    {
        $connection = $this->modelManager->getConnection();

        $result = $connection->executeQuery('SELECT active FROM s_articles_details WHERE id = ?', [$detailId])->fetchColumn();

        static::assertIsString($result);

        return $result;
    }
}
