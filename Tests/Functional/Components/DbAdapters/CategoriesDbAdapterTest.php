<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class CategoriesDbAdapterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteShouldThrowExceptionIfRecordsAreEmpty(): void
    {
        $categoriesDbAdapter = $this->getCategoriesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Es wurden keine Kategorien gefunden.');
        $categoriesDbAdapter->write([]);
    }

    public function testWriteShouldThrowExceptionIfParentCategoryDoesNotExist(): void
    {
        $categoryRecords = ['default' => [
                ['categoryId' => '123', 'name' => 'Category with invalid parent', 'parentId' => '123123'],
            ],
        ];

        $categoriesDbAdapter = $this->getCategoriesDbAdapter();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Vaterkategorie existiert nicht für Kategorie');
        $categoriesDbAdapter->write($categoryRecords);
    }

    public function testWriteShouldNotIncrementIdOnCreationWhenIdIsGiven(): void
    {
        $categoryRecords = ['default' => [
                ['categoryId' => '99999', 'name' => 'New Category', 'parentId' => '3'],
                ['categoryId' => '100000', 'name' => 'Second New Category', 'parentId' => '99999'],
            ],
        ];

        $categoriesDbAdapter = $this->getCategoriesDbAdapter();
        $categoriesDbAdapter->write($categoryRecords);

        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $createdCategory = $dbalConnection->executeQuery("SELECT * FROM s_categories WHERE description='New Category'")->fetchAll();
        $createdCategory2 = $dbalConnection->executeQuery("SELECT * FROM s_categories WHERE description='Second New Category'")->fetchAll();

        static::assertEquals($categoryRecords['default'][0]['categoryId'], $createdCategory[0]['id']);
        static::assertEquals($categoryRecords['default'][1]['categoryId'], $createdCategory2[0]['id']);
    }

    public function testWriteShouldThrowExceptionIfCategoryHasNoId(): void
    {
        $categoryRecords = ['default' => [
                ['name' => 'New Category', 'parentId' => '3'],
            ],
        ];
        $categoriesDbAdapter = $this->getCategoriesDbAdapter();

        $this->expectExceptionMessage('Die Kategorie ID ist ein Pflichtfeld. Wenn keine ID mitimportiert wird wäre es nicht möglich Kind- und Vaterkategorien zu referenzieren.');
        $this->expectException(\Exception::class);
        $categoriesDbAdapter->write($categoryRecords);
    }

    private function getCategoriesDbAdapter(): CategoriesDbAdapter
    {
        return $this->getContainer()->get(CategoriesDbAdapter::class);
    }
}
