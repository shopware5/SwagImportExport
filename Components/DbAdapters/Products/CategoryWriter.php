<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use Enlight_Event_EventManager as EventManager;
use Shopware\Components\Model\CategorySubscriber;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class CategoryWriter
{
    private PDOConnection $db;

    private Connection $connection;

    private array $categoryIds;

    private EventManager $eventManager;

    private CategorySubscriber $categorySubscriber;

    /**
     * initialises the class properties
     */
    public function __construct(
        PDOConnection $db,
        Connection $connection,
        EventManager $eventManager,
        CategorySubscriber $categorySubscriber
    ) {
        $this->db = $db;
        $this->connection = $connection;
        $this->eventManager = $eventManager;
        $this->categorySubscriber = $categorySubscriber;
    }

    /**
     * @param array<int, array<string, int|string>> $categories
     */
    public function write(int $productId, array $categories): void
    {
        if (!$categories) {
            return;
        }

        $values = $this->prepareValues($categories, $productId);

        $values = $this->eventManager->filter(
            'Shopware_Components_SwagImportExport_DbAdapters_Articles_CategoryWriter_Write',
            $values,
            ['subject' => $this]
        );

        $sql = "
            INSERT INTO s_articles_categories (articleID, categoryID)
            VALUES {$values}
            ON DUPLICATE KEY UPDATE categoryID=VALUES(categoryID), articleID=VALUES(articleID)
        ";

        $this->connection->executeStatement($sql);

        $this->updateProductsCategoriesRO($productId);
    }

    /**
     * Checks whether a category with the given id exists
     */
    private function isCategoryExists(int $categoryId): bool
    {
        $isCategoryExists = $this->db->fetchOne(
            'SELECT id FROM s_categories WHERE id = ?',
            [$categoryId]
        );

        return \is_numeric($isCategoryExists);
    }

    /**
     * Returns categoryId by path
     *
     * @param string $categoryPath -> 'English->Cars->Mazda'
     */
    private function getCategoryId(string $categoryPath): int
    {
        $id = null;
        $path = '|';
        $data = [];
        foreach (\explode('->', $categoryPath) as $description) {
            $id = $this->getId($description, $id ? (int) $id : null, $path);
            $path = '|' . $id . $path;
            $data[$id] = $description;
        }

        $categoryIds = \array_keys($data);

        return (int) \end($categoryIds);
    }

    /**
     * Checks whether a category with the given name exists and returns its id.
     * Creates a category if it does not exist and returns the new inserted id.
     *
     * @throws AdapterException
     *
     * @return int - categoryId
     */
    private function getId(string $description, ?int $id, string $path): int
    {
        if ($id === null) {
            $sql = 'SELECT id FROM s_categories WHERE description = ? AND path IS NULL';
            $params = [$description];
        } else {
            $sql = 'SELECT id FROM s_categories WHERE description = ? AND parent = ?';
            $params = [$description, $id];
        }

        $parentId = $this->db->fetchOne($sql, $params);

        // check whether we have more than one category on the same level with the same name
        $count = $this->db->fetchCol($sql, $params);
        if (\count($count) > 1) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/category_duplicated', "Category with name '%s' is duplicated");
            throw new AdapterException(\sprintf($message, $description));
        }

        // check whether the category should be created
        if (!\is_numeric($parentId)) {
            $parentId = $this->insertCategory($description, $id, $path);
            $this->insertCategoryAttributes($parentId);
        }

        return (int) $parentId;
    }

    /**
     * Creates a category and returns its id
     *
     * @return int created category id
     */
    private function insertCategory(string $description, ?int $id, string $path): int
    {
        if ($id === null) {
            $this->isRootExists();
            $values = "(1, NULL, NOW(), NOW(), '{$description}', 1, 0, 0, 0, 0, 0, 0)";
        } else {
            $values = "({$id}, '{$path}', NOW(), NOW(), '{$description}', 1, 0, 0, 0, 0, 0, 0)";
        }

        $sql = "INSERT INTO s_categories (`parent`, `path`, `added`, `changed`, `description`, `active`, `left`, `right`, `level`, `blog`, `hidefilter`, `hidetop`)
                VALUES {$values}";

        $this->db->exec($sql);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @throws \RuntimeException
     */
    private function isRootExists(): void
    {
        $sql = 'SELECT id FROM s_categories WHERE id = 1';
        $rootId = $this->db->fetchOne($sql);

        if ($rootId === false) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/root_category_does_not_exist', 'Root category does not exist');
            throw new \RuntimeException($message);
        }
    }

    /**
     * Creates categories' attributes
     */
    private function insertCategoryAttributes(int $categoryId): void
    {
        $sql = "INSERT INTO s_categories_attributes (categoryID) VALUES ({$categoryId})";
        $this->db->exec($sql);
    }

    /**
     * Checks whether the category is a leaf
     */
    private function isLeaf(int $categoryId): bool
    {
        $isParent = $this->db->fetchOne(
            'SELECT id FROM s_categories WHERE parent = ?',
            [$categoryId]
        );

        return $isParent === false;
    }

    private function updateProductsCategoriesRO(int $productId): void
    {
        foreach ($this->categoryIds as $categoryId) {
            $this->categorySubscriber->backlogAddAssignment($productId, $categoryId);
        }
    }

    /**
     * @param array<int, array<string, int|string>> $categories
     */
    private function prepareValues(array $categories, int $productId): string
    {
        $this->categoryIds = [];

        return \implode(
            ', ',
            \array_map(
                function (array $category) use ($productId): string {
                    $isCategoryExists = false;
                    if (!empty($category['categoryId'])) {
                        $isCategoryExists = $this->isCategoryExists((int) $category['categoryId']);
                    }

                    // if categoryId exists, the article will be assigned to it, no matter of the categoryPath
                    if ($isCategoryExists === true) {
                        $this->categoryIds[$category['categoryId']] = (int) $category['categoryId'];

                        return "({$productId}, {$category['categoryId']})";
                    }

                    // if categoryId does NOT exist and categoryPath is empty an error will be shown
                    if ($isCategoryExists === false && empty($category['categoryPath'])) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/articles/category_not_found', 'Category with id %s could not be found.');
                        throw new AdapterException(\sprintf($message, $category['categoryId']));
                    }

                    // if categoryPath exists, the article will be assign based on the path
                    if (!empty($category['categoryPath'])) {
                        // get categoryId by given path: 'English->Cars->Mazda'
                        $category['categoryId'] = $this->getCategoryId((string) $category['categoryPath']);

                        // check whether the category is a leaf
                        if (!$this->isLeaf($category['categoryId'])) {
                            $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articles/category_not_leaf', "Category with id '%s' is not a leaf");
                            throw new AdapterException(\sprintf($message, $category['categoryId']));
                        }

                        $this->categoryIds[$category['categoryId']] = $category['categoryId'];

                        return "({$productId}, {$category['categoryId']})";
                    }

                    return '';
                },
                $categories
            )
        );
    }
}
