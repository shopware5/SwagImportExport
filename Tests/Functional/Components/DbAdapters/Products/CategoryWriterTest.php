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
use SwagImportExport\Components\DbAdapters\Products\CategoryWriter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class CategoryWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteWithInvalidCategoryIdThrowsException(): void
    {
        $categoryWriterAdapter = $this->getCategoryWriterAdapter();
        $validProductId = 3;
        $invalidCategoryArray = [
            [
                'categoryId' => 9999,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kategorie mit ID 9999 konnte nicht gefunden werden.');
        $categoryWriterAdapter->write($validProductId, $invalidCategoryArray);
    }

    public function testWriteWithNoCategoryIdAndNewPathCreatesCategories(): void
    {
        $categoryWriterAdapter = $this->getCategoryWriterAdapter();
        $validProductId = 3;
        $invalidCategoryArray = [
            [
                'categoryId' => '',
                'categoryPath' => 'Brand->New->Category->Path',
            ],
        ];

        $categoryWriterAdapter->write($validProductId, $invalidCategoryArray);
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $productCategories = $dbalConnection->executeQuery('SELECT * FROM s_categories c LEFT JOIN s_articles_categories ac ON ac.categoryID = c.id WHERE ac.articleID=?', [3])->fetchAll();

        static::assertSame('Path', $productCategories[3]['description']);
    }

    public function testWriteShouldInsertProductCategoryAssociation(): void
    {
        $categoryWriterAdapter = $this->getCategoryWriterAdapter();
        $productId = 3;
        $categoryArray = [
            [
                'categoryId' => 35,
            ],
        ];

        $categoryWriterAdapter->write($productId, $categoryArray);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $updatedProduct = $dbalConnection->executeQuery('SELECT * FROM s_articles_categories WHERE articleID=?', [$productId])->fetchAll();

        static::assertEquals($categoryArray[0]['categoryId'], $updatedProduct[2]['categoryID']);
    }

    private function getCategoryWriterAdapter(): CategoryWriter
    {
        return $this->getContainer()->get(CategoryWriter::class);
    }
}
