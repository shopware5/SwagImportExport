<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use Enlight_Event_EventManager as EventManager;
use Shopware\Components\Model\CategorySubscriber;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class CategoryWriter
{
    /**
     * @var PDOConnection
     */
    protected $db;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $categoryIds;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * initialises the class properties
     */
    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->connection = Shopware()->Models()->getConnection();
        $this->eventManager = Shopware()->Events();
    }

    /**
     * @param string $articleId
     * @param array  $categories
     *
     * @throws DBALException
     */
    public function write($articleId, $categories)
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
     *
     * @return bool
     */
    protected function isCategoryExists($categoryId)
    {
        $isCategoryExists = $this->db->fetchOne(
            'SELECT id FROM s_categories WHERE id = ?',
            [$categoryId]
        );

        return is_numeric($isCategoryExists);
    }

    /**
     * Returns categoryId by path
     *
     * @param string $categoryPath -> 'English->Cars->Mazda'
     *
     * @throws AdapterException
     *
     * @return int|string - categoryId
     */
    protected function getCategoryId($categoryPath)
    {
        $id = null;
        $path = '|';
        $data = [];
        $descriptions = explode('->', $categoryPath);

        foreach ($descriptions as $description) {
            $id = $this->getId($description, $id, $path);
            $path = '|' . $id . $path;
            $data[$id] = $description;
        }

        $categoryIds = array_keys($data);

        return end($categoryIds);
    }

    /**
     * Checks whether a category with the given name exists and returns its id.
     * Creates a category if it does not exist and returns the new inserted id.
     *
     * @param mixed $description - category name
     * @param mixed $id          - parent id
     * @param mixed $path        - category path
     *
     * @throws AdapterException
     *
     * @return int|string - categoryId
     */
    protected function getId($description, $id, $path)
    {
        if ($id === null) {
            $sql = 'SELECT id FROM s_categories WHERE description = ? AND path IS NULL';
            $params = [$description];
        } else {
            $sql = 'SELECT id FROM s_categories WHERE description = ? AND parent = ?';
            $params = [$description, $id];
        }

        $parentId = $this->db->fetchOne($sql, $params);

        //check whether we have more than one category on the same level with the same name
        $count = $this->db->fetchCol($sql, $params);
        if (count($count) > 1) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/category_duplicated', "Category with name '%s' is duplicated");
            throw new AdapterException(sprintf($message, $description));
        }

        //check whether the category should be created
        if (!is_numeric($parentId)) {
            $parentId = $this->insertCategory($description, $id, $path);
            $this->insertCategoryAttributes($parentId);
        }

        return $parentId;
    }

    /**
     * Creates a category and returns its id
     *
     * @param mixed $description - category name
     * @param mixed $id          - id of the parent category
     * @param mixed $path        - category path
     *
     * @return int created category id
     */
    protected function insertCategory($description, $id, $path)
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

        return $this->db->lastInsertId();
    }

    /**
     * @throws \RuntimeException
     */
    protected function isRootExists()
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
     *
     * @param int $categoryId
     */
    protected function insertCategoryAttributes($categoryId)
    {
        $sql = "INSERT INTO s_categories_attributes (categoryID) VALUES ({$categoryId})";
        $this->db->exec($sql);
    }

    /**
     * Checks whether the category is a leaf
     *
     * @param int $categoryId
     *
     * @return bool
     */
    protected function isLeaf($categoryId)
    {
        $isParent = $this->db->fetchOne(
            'SELECT id FROM s_categories WHERE parent = ?',
            [$categoryId]
        );

        return $isParent === false;
    }

    /**
     * Updates s_articles_categories_ro table
     *
     * @param string $articleId
     */
    protected function updateArticlesCategoriesRO($articleId)
    {
        /** @var CategorySubscriber $categorySubscriber */
        $categorySubscriber = Shopware()->Container()->get('categorysubscriber');
        foreach ($this->categoryIds as $categoryId) {
            $categorySubscriber->backlogAddAssignment($articleId, $categoryId);
        }
    }

    /**
     * @param array  $categories
     * @param string $articleId
     *
     * @return string
     */
    private function prepareValues($categories, $articleId)
    {
        $this->categoryIds = [];
        $values = implode(
            ', ',
            array_map(
                function ($category) use ($articleId) {
                    $isCategoryExists = false;
                    if (!empty($category['categoryId'])) {
                        $isCategoryExists = $this->isCategoryExists($category['categoryId']);
                    }

                    //if categoryId exists, the article will be assigned to it, no matter of the categoryPath
                    if ($isCategoryExists === true) {
                        $this->categoryIds[$category['categoryId']] = (int) $category['categoryId'];

                        return "({$articleId}, {$category['categoryId']})";
                    }

                    //if categoryId does NOT exist and categoryPath is empty an error will be shown
                    if ($isCategoryExists === false && empty($category['categoryPath'])) {
                        $message = SnippetsHelper::getNamespace()
                            ->get('adapters/articles/category_not_found', 'Category with id %s could not be found.');
                        throw new AdapterException(sprintf($message, $category['categoryId']));
                    }

                    //if categoryPath exists, the article will be assign based on the path
                    if (!empty($category['categoryPath'])) {
                        //get categoryId by given path: 'English->Cars->Mazda'
                        $category['categoryId'] = $this->getCategoryId($category['categoryPath']);

                        //check whether the category is a leaf
                        $isLeaf = $this->isLeaf($category['categoryId']);

                        if (!$isLeaf) {
                            $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articles/category_not_leaf', "Category with id '%s' is not a leaf");
                            throw new AdapterException(sprintf($message, $category['categoryId']));
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
