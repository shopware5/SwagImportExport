<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class CategoriesDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty()
    {
        $categoriesDbAdapter = $this->createCategoriesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Kategorien gefunden.');
        $categoriesDbAdapter->write([]);
    }

    public function testWriteShouldThrowExceptionIfParentCategoryDoesNotExist()
    {
        $categoryRecords = ['default' => [
                ['categoryId' => '123', 'name' => 'Category with invalid parent', 'parentId' => '123123'],
            ],
        ];

        $categoriesDbAdapter = $this->createCategoriesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Vaterkategorie existiert nicht für Kategorie');
        $categoriesDbAdapter->write($categoryRecords);
    }

    public function testWriteShouldNotIncrementIdOnCreationWhenIdIsGiven()
    {
        $categoryRecords = ['default' => [
                ['categoryId' => '99999', 'name' => 'New Category', 'parentId' => '3'],
                ['categoryId' => '100000', 'name' => 'Second New Category', 'parentId' => '99999'],
            ],
        ];

        $categoriesDbAdapter = $this->createCategoriesDbAdapter();
        $categoriesDbAdapter->write($categoryRecords);

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        $createdCategory = $dbalConnection->executeQuery("SELECT * FROM s_categories WHERE description='New Category'")->fetchAll();
        $createdCategory2 = $dbalConnection->executeQuery("SELECT * FROM s_categories WHERE description='Second New Category'")->fetchAll();

        static::assertEquals($categoryRecords['default'][0]['categoryId'], $createdCategory[0]['id']);
        static::assertEquals($categoryRecords['default'][1]['categoryId'], $createdCategory2[0]['id']);
    }

    public function testWriteShouldThrowExceptionIfCategoryHasNoId()
    {
        $categoryRecords = ['default' => [
                ['name' => 'New Category', 'parentId' => '3'],
            ],
        ];
        $categoriesDbAdapter = $this->createCategoriesDbAdapter();

        $this->expectExceptionMessage('Die Kategorie ID ist ein Pflichtfeld. Wenn keine ID mitimportiert wird wäre es nicht möglich Kind- und Vaterkategorien zu referenzieren.');
        $this->expectException(\Exception::class);
        $categoriesDbAdapter->write($categoryRecords);
    }

    /**
     * @return CategoriesDbAdapter
     */
    private function createCategoriesDbAdapter()
    {
        return new CategoriesDbAdapter();
    }
}
