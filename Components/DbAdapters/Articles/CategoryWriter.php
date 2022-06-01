<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use Enlight_Event_EventManager as EventManager;
use Shopware\Components\Model\CategorySubscriber;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class CategoryWriter
{
    protected PDOConnection $db;

    protected Connection $connection;

    protected array $categoryIds;

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
     *
     * @throws DBALException
     */
    public function write(string $articleId, array $categories): void
    {
        if (!$categories) {
            return;
        }

        $values = $this->prepareValues($categories, $articleId);

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

        $this->connection->exec($sql);

        $this->updateArticlesCategoriesRO($articleId);
    }

    /**
     * Checks whether a category with the given id exists
     */
    protected function isCategoryExists(int $categoryId): bool
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
    protected function getCategoryId(string $categoryPath): int
    {
        $id = null;
        $path = '|';
        $data = [];
        $descriptions = \explode('->', $categoryPath);

        foreach ($descriptions as $description) {
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
    protected function getId(string $description, ?int $id, string $path): int
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
    protected function insertCategory(string $description, ?int $id, string $path): int
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
    protected function isRootExists(): void
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
    protected function insertCategoryAttributes(int $categoryId): void
    {
        $sql = "INSERT INTO s_categories_attributes (categoryID) VALUES ({$categoryId})";
        $this->db->exec($sql);
    }

    /**
     * Checks whether the category is a leaf
     */
    protected function isLeaf(int $categoryId): bool
    {
        $isParent = $this->db->fetchOne(
            'SELECT id FROM s_categories WHERE parent = ?',
            [$categoryId]
        );

        return $isParent === false;
    }

    /**
     * Updates s_articles_categories_ro table
     */
    protected function updateArticlesCategoriesRO(string $articleId): void
    {
        foreach ($this->categoryIds as $categoryId) {
            $this->categorySubscriber->backlogAddAssignment($articleId, $categoryId);
        }
    }

    /**
     * @param array<string, mixed> $categories
     */
    private function prepareValues(array $categories, string $articleId): string
    {
        $this->categoryIds = [];
        $values = \implode(
            ', ',
            \array_map(
                function ($category) use ($articleId) {
                    $isCategoryExists = false;
                    if (!empty($category['categoryId'])) {
                        $isCategoryExists = $this->isCategoryExists($category['categoryId']);
                    }

                    // if categoryId exists, the article will be assigned to it, no matter of the categoryPath
                    if ($isCategoryExists === true) {
                        $this->categoryIds[$category['categoryId']] = (int) $category['categoryId'];

                        return "({$articleId}, {$category['categoryId']})";
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
                        $category['categoryId'] = $this->getCategoryId($category['categoryPath']);

                        // check whether the category is a leaf
                        $isLeaf = $this->isLeaf($category['categoryId']);

                        if (!$isLeaf) {
                            $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articles/category_not_leaf', "Category with id '%s' is not a leaf");
                            throw new AdapterException(\sprintf($message, $category['categoryId']));
                        }

                        $this->categoryIds[$category['categoryId']] = (int) $category['categoryId'];

                        return "({$articleId}, {$category['categoryId']})";
                    }
                },
                $categories
            )
        );

        return $values;
    }
}
