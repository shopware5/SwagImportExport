<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\CategoryWriter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class CategoryWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testWriteWithInvalidCategoryIdThrowsException()
    {
        $categoryWriterAdapter = $this->createCategoryWriterAdapter();
        $validArticleId = 3;
        $invalidCategoryArray = [
            [
                'categoryId' => 9999,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kategorie mit ID 9999 konnte nicht gefunden werden.');
        $categoryWriterAdapter->write($validArticleId, $invalidCategoryArray);
    }

    public function testWriteWithNoCategoryIdAndNewPathCreatesCategories()
    {
        $categoryWriterAdapter = $this->createCategoryWriterAdapter();
        $validArticleId = 3;
        $invalidCategoryArray = [
            [
                'categoryId' => '',
                'categoryPath' => 'Brand->New->Category->Path',
            ],
        ];

        $categoryWriterAdapter->write($validArticleId, $invalidCategoryArray);
        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $articleCategories = $dbalConnection->executeQuery('SELECT * FROM s_categories c LEFT JOIN s_articles_categories ac ON ac.categoryID = c.id WHERE ac.articleID=?', [3])->fetchAll();

        static::assertSame('Path', $articleCategories[3]['description']);
    }

    public function testWriteShouldInsertArticleCategoryAssociation()
    {
        $categoryWriterAdapter = $this->createCategoryWriterAdapter();
        $articleId = 3;
        $categoryArray = [
            [
                'categoryId' => 35,
            ],
        ];

        $categoryWriterAdapter->write($articleId, $categoryArray);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $updatedArticle = $dbalConnection->executeQuery('SELECT * FROM s_articles_categories WHERE articleID=?', [$articleId])->fetchAll();

        static::assertEquals($categoryArray[0]['categoryId'], $updatedArticle[2]['categoryID']);
    }

    /**
     * @return CategoryWriter
     */
    private function createCategoryWriterAdapter()
    {
        return new CategoryWriter();
    }
}
