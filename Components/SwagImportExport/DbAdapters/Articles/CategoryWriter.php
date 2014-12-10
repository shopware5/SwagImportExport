<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\QueryBuilder\QueryBuilder;

class CategoryWriter
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
    }

    public function write($articleId, $categories)
    {
        if (!$categories) {
            return;
        }
        $values = implode(
            ', ',
            array_map(
                function ($category) use ($articleId) {
                    return "({$articleId}, {$category['categoryId']})";
                },
                $categories
            )
        );

        $sql = "
            INSERT INTO s_articles_categories (articleID, categoryID)
            VALUES {$values}
            ON DUPLICATE KEY UPDATE categoryID=VALUES(categoryID), articleID=VALUES(articleID)
        ";

        $this->connection->exec($sql);
    }
}