<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class CategoryWriter
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    protected $categoryIds = array();

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->connection = Shopware()->Models()->getConnection();
    }

    public function write($articleId, $categories)
    {
        if (!$categories) {
            return;
        }

        $values = $this->prepareValues($categories, $articleId);
        $sql = "
            INSERT INTO s_articles_categories (articleID, categoryID)
            VALUES {$values}
            ON DUPLICATE KEY UPDATE categoryID=VALUES(categoryID), articleID=VALUES(articleID)
        ";

        $this->connection->exec($sql);

        $this->updateArticlesCategoriesRO($articleId);
    }

    protected function prepareValues($categories, $articleId) {
        $values = implode(
            ', ',
            array_map(
                function ($category) use ($articleId) {
                    if (!empty($category['categoryId'])) {
                        $isCategoryExists = $this->isCategoryExists($category['categoryId']);
                    }

                    if (!empty($category['categoryId']) && !$isCategoryExists && !empty($category['categoryPath']) ||
                        empty($category['categoryId']) &&  !empty($category['categoryPath'])
                    ) {
                        $category['categoryId'] = $this->getCategoryId($category['categoryPath']);
                        $isNotLeaf = $this->isNotLeaf($category['categoryId']);
                        if ($isNotLeaf && !$isCategoryExists) {
                            $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articles/category_not_found', "Category with id %s could not be found.");
                            throw new AdapterException(sprintf($message, $category['categoryId']));
                        } elseif ($isNotLeaf) {
                            $message = SnippetsHelper::getNamespace()
                                ->get('adapters/articles/category_not_leaf', "Category with id '%s' is not a leaf");
                            throw new AdapterException(sprintf($message, $category['categoryId']));
                        }
                    }

                    $this->categoryIds[$category['categoryId']] = (int) $category['categoryId'];
                    return "({$articleId}, {$category['categoryId']})";
                },
                $categories
            )
        );

        return $values;
    }

    protected function isCategoryExists($categoryId)
    {
        $isCategoryExists = $this->db->fetchOne(
            "SELECT id FROM s_categories WHERE id = ?",
            array($categoryId)
        );

        return is_numeric($isCategoryExists);
    }

    protected function getCategoryId($categoryPath)
    {
        $id = null;
        $path = '|';
        $data = array();
        $descriptions = explode('->', $categoryPath);
        $count = count($descriptions);

        for ($i = 0; $i < $count; $i++) {
            $id = $this->getId($descriptions[$i], $id, $path);
            $path = '|' . $id . $path;
            $data[$id] = $descriptions[$i];
        }

        return end(array_keys($data));
    }

    protected function getId($description, $id, $path)
    {
        if ($id === null) {
            $sql = "SELECT id FROM s_categories WHERE description = ?";
            $params = array($description);
        } else {
            $sql = "SELECT id FROM s_categories WHERE description = ? AND parent = ?";
            $params = array($description, $id);
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
        }

        return $parentId;
    }

    protected function insertCategory($description, $id, $path)
    {
        if ($id === null) {
            $values = "(1, NULL, NOW(), NOW(), '{$description}', 1, 1)";
        } else {
            $values = "({$id}, '{$path}', NOW(), NOW(), '{$description}', 1, 1)";
        }

        $sql = "
            INSERT INTO s_categories (parent, path, added, changed, description, active, showfiltergroups)
            VALUES {$values}
        ";

        $this->db->exec($sql);
        $insertedId = $this->db->lastInsertId();

        return $insertedId;
    }

    protected function isNotLeaf($categoryId)
    {
        $isLeaf = $this->db->fetchOne(
            "SELECT id FROM s_categories WHERE parent = ?",
            array($categoryId)
        );

        return is_numeric($isLeaf);
    }

    protected function updateArticlesCategoriesRO($articleId)
    {
        foreach ($this->categoryIds as $categoryId) {
            Shopware()->CategorySubscriber()->backlogAddAssignment($articleId, $categoryId);
        }
    }
}