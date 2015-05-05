<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

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
                        if (!$isCategoryExists) {
                            //TODO: throw an exception
                        }
                    } elseif (!empty($category['categoryPath']) ) {
                        $category['categoryId'] = $this->getCategoryId($category['categoryPath']);
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
        //TODO: check for new categories
        $data = array();
        $descriptions = explode('->', $categoryPath);
        $count = count($descriptions);

        for ($i = 0; $i < $count; $i++) {
            $id = $this->getParentId($descriptions[$i]);
            $data[] = $id;

            $id = $this->getChildId($id, $descriptions[++$i]);
            $data[] = $id;
        }

        return end($data);
    }

    protected function getParentId($description)
    {
        $parentId = $this->db->fetchOne(
            "SELECT id FROM s_categories WHERE description = ?",
            array($description)
        );

        //TODO: check if exists
        return $parentId;
    }

    protected function getChildId($parentId, $description)
    {
        $childId = $this->db->fetchOne(
            "SELECT id FROM s_categories WHERE description = ? AND parent = ?",
            array($description, $parentId)
        );

        //TODO: check if exists
        return $childId;
    }

    protected function updateArticlesCategoriesRO($articleId)
    {
        foreach ($this->categoryIds as $categoryId) {
            Shopware()->CategorySubscriber()->backlogAddAssignment($articleId, $categoryId);
        }
    }
}