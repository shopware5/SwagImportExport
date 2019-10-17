<?php
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

    protected function setUp()
    {
        parent::setUp();
        $this->modelManager = Shopware()->Container()->get('models');
        $this->modelManager->beginTransaction();

        $this->articleWriter = new ArticleWriter();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->modelManager->rollback();
    }

    public function test_write()
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

        $this->assertEquals($expectedArticleId, $articleWriterResult->getArticleId(), 'Expected articleId does not match the obtained articleId.');
        $this->assertEquals($expectedDetailId, $articleWriterResult->getDetailId(), 'Expected detailId does not match the obtained detailId.');
        $this->assertEquals($expectedMainDetailId, $articleWriterResult->getMainDetailId(), 'Expected mainDetailId id does not match the obtained detailId.');
    }

    public function test_write_should_insert_a_new_article()
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

        $this->assertNotNull($insertedArticle, 'Could not insert article');
        $this->assertEquals($expectedNewArticle['orderNumber'], $insertedArticle->getMainDetail()->getNumber(), 'Could not insert field ordernumber.');
        $this->assertEquals($expectedNewArticle['description'], $insertedArticle->getDescription(), 'Could not insert field description.');
        $this->assertEquals($expectedNewArticle['descriptionLong'], $insertedArticle->getDescriptionLong(), 'Could not insert field descrption_long.');
        $this->assertEquals($expectedNewArticle['inStock'], $insertedArticle->getMainDetail()->getInStock(), 'Could not insert field instock.');
        $this->assertEquals($expectedNewArticle['active'], $insertedArticle->getActive(), 'Could not insert field active.');
        $this->assertEquals($expectedNewArticle['supplierName'], $insertedArticle->getSupplier()->getName(), 'Could not insert field supplier name.');
    }

    public function test_write_should_update_an_existing_article()
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
        ];

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        /** @var Article $updatedArticle */
        $updatedArticle = $this->modelManager->find(Article::class, $articleWriterResult->getArticleId());

        $this->assertNotNull($updatedArticle, 'Could not find updated article');
        $this->assertEquals($expectedModifiedArticle['orderNumber'], $updatedArticle->getMainDetail()->getNumber(), 'Could not update field ordernumber.');
        $this->assertEquals($expectedModifiedArticle['description'], $updatedArticle->getDescription(), 'Could not update field description.');
        $this->assertEquals($expectedModifiedArticle['descriptionLong'], $updatedArticle->getDescriptionLong(), 'Could not update field description long.');
        $this->assertEquals($expectedModifiedArticle['inStock'], $updatedArticle->getMainDetail()->getInStock(), 'Could not update field instock.');
        $this->assertEquals($expectedModifiedArticle['lastStock'], $updatedArticle->getMainDetail()->getLastStock(), 'Could not update field last stock.');
        $this->assertFalse($updatedArticle->getActive(), 'Could not update field active.');
        $this->assertEquals($expectedModifiedArticle['supplierName'], $updatedArticle->getSupplier()->getName(), 'Could not update field supplier name.');
    }

    public function test_write_with_processed_article()
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

        $this->assertEquals($articleWriterResult->getArticleId(), $expectedId, 'The expected article id do not match');
        $this->assertEquals($articleWriterResult->getDetailId(), $expectedDetailId, 'The expected article detail id do not match');
        $this->assertEquals($articleWriterResult->getMainDetailId(), $expectedMainDetailId, 'The expected article main detail id do not match');
    }

    public function test_write_detail_with_not_existing_main_detail_should_throw_exception()
    {
        $expectedModifiedArticle = [
            'orderNumber' => 'number_does_not_exist',
            'mainNumber' => 'number_does_not_exist_and_is_different',
        ];

        $this->expectException(AdapterException::class);
        $this->articleWriter->write($expectedModifiedArticle, []);
    }

    public function test_write_should_update_article_active_flag_if_main_detail_active_flag_is_given()
    {
        $expectedModifiedArticle = [
            'orderNumber' => 'SW10123.1',
            'mainNumber' => 'SW10123.1',
            'active' => '0',
        ];

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        /** @var Article $article */
        $isMainArticleActive = $this->getArticlesActiveFlag($articleWriterResult->getArticleId());
        $isMainDetailActive = $this->getArticleDetailActiveFlag($articleWriterResult->getDetailId());

        $this->assertEquals(0, $isMainDetailActive, 'Could not update active flag for article main detail.');
        $this->assertEquals(0, $isMainArticleActive, 'Could not update active flag for s_articles if main detail active flag is given.');
    }

    public function test_write_should_not_update_article_active_flag_if_detail_active_flag_is_given()
    {
        $expectedModifiedArticle = [
            'orderNumber' => 'SW10123.2',
            'mainNumber' => 'SW10123.1',
            'active' => '0',
        ];

        $articleWriterResult = $this->articleWriter->write($expectedModifiedArticle, []);

        $isMainArticleActive = $this->getArticlesActiveFlag($articleWriterResult->getArticleId());
        $isDetailActive = $this->getArticleDetailActiveFlag($articleWriterResult->getDetailId());

        $this->assertEquals(0, $isDetailActive, 'Could not update article detail active flag.');
        $this->assertEquals(1, $isMainArticleActive, 'Article active flag was updated, but only article detail should be updated.');
    }

    /**
     * @param $articleId
     *
     * @return bool|string
     */
    protected function getArticlesActiveFlag($articleId)
    {
        $connection = $this->modelManager->getConnection();

        return $connection->executeQuery('SELECT active FROM s_articles WHERE id = ?', [$articleId])->fetchColumn();
    }

    /**
     * @param int $detailId
     *
     * @return bool|string
     */
    protected function getArticleDetailActiveFlag($detailId)
    {
        $connection = $this->modelManager->getConnection();

        return $connection->executeQuery('SELECT active FROM s_articles_details WHERE id = ?', [$detailId])->fetchColumn();
    }
}
