<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\QueryBuilder\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class Write
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
    }

    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->connection);
    }

    public function write($articles)
    {
        foreach ($articles as $article) {
            if (!isset($article['orderNumber']) && empty($article['orderNumber'])) {
                $message = SnippetsHelper::getNamespace()->get(
                    'adapters/ordernumber_required',
                    'Order number is required.'
                );
                throw new AdapterException($message);
            }
            list($articleId, $articleDetailId, $mainDetailId) = $this->insertOrUpdateArticle($article);
        }
    }

    protected function insertOrUpdateArticle($article)
    {
        $articleId = null;
        $mainDetailId = null;
        $detailId = null;

        if ($article['mainNumber']) {
            $result = $this->db->fetchRow(
                'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
                $article['mainNumber']
            );
            if (!empty($result)) {
                $mainDetailId = $result['id'];
                $articleId = $result['articleID'];
            }
        }

        $result = $this->db->fetchRow(
            'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
            array($article['orderNumber'])
        );
        if (!empty($result)) {
            $detailId = $result['id'];
            $articleId = $result['articleID'];
        }
        $createDetail = $detailId > 0;

        if ($createDetail && !$mainDetailId) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/variant_existence',
                'Variant with number %s does not exists.'
            );
            throw new AdapterException(sprintf($message, $article['mainNumber']));
        }

        $createArticle = false;
        if ($createDetail && $article['mainNumber'] == $article['orderNumber']) {
            $createArticle = true;
        }

        $builder = $this->getQueryBuilderForEntity($article, $createArticle ? $articleId : false, 'Shopware\Models\Article\Article');
        $builder->execute();
        $articleId = $this->db->lastInsertId();

        $article['number'] = $article['orderNumber'];
        $article['articleId'] = $articleId;
        $article['kind'] = $createArticle ? 1 : 2;
        $builder = $this->getQueryBuilderForEntity($article, $detailId, 'Shopware\Models\Article\Detail');
        $builder->execute();

        if (!$detailId) {
            $detailId = $this->db->lastInsertId();
        }

        if ($createArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', array($detailId, $articleId));
        }
    }

    protected function getQueryBuilderForEntity($data, $entity, $primaryId)
    {
        $metaData = Shopware()->Models()->getClassMetadata($entity);
        $table = $metaData->table['name'];

        $builder = $this->getQueryBuilder();

        if ($primaryId) {
            $builder->update($table);
            $builder->where('id = ' . $builder->createNamedParameter($primaryId, \PDO::PARAM_INT));
        } else {
            $builder->insert($table);
        }

        foreach ($data as $field => $value) {
            if (!array_key_exists($field, $metaData->fieldMappings)) {
                continue;
            }

            $key = $metaData->fieldMappings[$field]['columnName'];

            $value = $this->getNamedParameter($value, $key, $metaData, $builder);
            if ($primaryId) {
                $builder->set($key, $value);
            } else {
                $builder->setValue($key, $value);
            }
        }

        return $builder;
    }


    protected function getNamedParameter($value, $key, $metaData, QueryBuilder $builder)
    {
        if (!array_key_exists($key, $metaData->fieldMappings)) {
            return false;
        }

        $pdoTypeMapping = array(
            'string' => \PDO::PARAM_STR,
            'text' => \PDO::PARAM_STR,
            'date' => \PDO::PARAM_STR,
            'datetime' => \PDO::PARAM_STR,
            'boolean' => \PDO::PARAM_INT,
            'integer' => \PDO::PARAM_INT,
            'decimal' => \PDO::PARAM_INT,
        );

        $nullAble = $metaData->fieldMappings[$key]['nullable'];

        // Check if nullable
        if (empty($value) && $nullAble) {
            return $builder->createNamedParameter(
                "NULL",
                \PDO::PARAM_NULL
            );
        }

        $type = $metaData->fieldMappings[$key]['type'];
        if (!array_key_exists($type, $pdoTypeMapping)) {
            throw new \RuntimeException("Type {$type} not found");
        }

        return $builder->createNamedParameter(
            $value,
            $pdoTypeMapping[]
        );
    }
}