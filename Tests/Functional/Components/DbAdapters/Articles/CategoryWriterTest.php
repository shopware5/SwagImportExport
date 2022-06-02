<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\Articles\CategoryWriter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class CategoryWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteWithInvalidCategoryIdThrowsException(): void
    {
        $categoryWriterAdapter = $this->getCategoryWriterAdapter();
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

    public function testWriteWithNoCategoryIdAndNewPathCreatesCategories(): void
    {
        $categoryWriterAdapter = $this->getCategoryWriterAdapter();
        $validArticleId = 3;
        $invalidCategoryArray = [
            [
                'categoryId' => '',
                'categoryPath' => 'Brand->New->Category->Path',
            ],
        ];

        $categoryWriterAdapter->write($validArticleId, $invalidCategoryArray);
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $articleCategories = $dbalConnection->executeQuery('SELECT * FROM s_categories c LEFT JOIN s_articles_categories ac ON ac.categoryID = c.id WHERE ac.articleID=?', [3])->fetchAll();

        static::assertSame('Path', $articleCategories[3]['description']);
    }

    public function testWriteShouldInsertArticleCategoryAssociation(): void
    {
        $categoryWriterAdapter = $this->getCategoryWriterAdapter();
        $articleId = 3;
        $categoryArray = [
            [
                'categoryId' => 35,
            ],
        ];

        $categoryWriterAdapter->write($articleId, $categoryArray);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedArticle = $dbalConnection->executeQuery('SELECT * FROM s_articles_categories WHERE articleID=?', [$articleId])->fetchAll();

        static::assertEquals($categoryArray[0]['categoryId'], $updatedArticle[2]['categoryID']);
    }

    private function getCategoryWriterAdapter(): CategoryWriter
    {
        return $this->getContainer()->get(CategoryWriter::class);
    }
}
