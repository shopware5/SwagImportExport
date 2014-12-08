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

        $builder = $this->getArticleBuilder($article, $createArticle ? $articleId : false);
        $builder->execute();
        $articleId = $this->db->lastInsertId();

        $article['number'] = $article['orderNumber'];
        $article['articleId'] = $articleId;
        $article['kind'] = $createArticle ? 1 : 2;
        $builder = $this->getArticleDetailBuilder($article, $articleId, $detailId);
        $builder->execute();
        error_log(print_r($builder->getSQL(), true) . "\n", 3, Shopware()->DocPath() . '/../error.log');
        error_log(print_r($builder->getParameters(), true) . "\n", 3, Shopware()->DocPath() . '/../error.log');
        if (!$detailId) {
            $detailId = $this->db->lastInsertId();
        }

        if ($createArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', array($detailId, $articleId));
        }
    }

    protected function getArticleDetailBuilder($article, $articleId, $detailId)
    {
        $builder = $this->getQueryBuilder();

        if ($detailId) {
            $builder->update('s_articles_details');
            $builder->where('id = ' . $builder->createNamedParameter($detailId, \PDO::PARAM_INT));
        } else {
            $builder->insert('s_articles_details');
        }

        foreach ($article as $field => $value) {
            $key = $this->mapFieldName($field, 'Shopware\Models\Article\Detail');
            if (!$key) {
                continue;
            }

            $type = $this->guessType($value, $field, 'Shopware\Models\Article\Article');
                $value = $builder->createNamedParameter(
                    empty($value) && $type == \PDO::PARAM_NULL ? "NULL" : $value,
                    $type
                );
            if ($detailId) {
                $builder->set($key, $value);
            } else {
                $builder->setValue($key, $value);
            }
        }

        return $builder;
    }

    protected function getArticleBuilder($article, $articleId = null)
    {
        $builder = $this->getQueryBuilder();

        if ($articleId) {
            $builder->update('s_articles');
            $builder->where('id = ' . $builder->createNamedParameter($articleId, \PDO::PARAM_INT));
        } else {
            $builder->insert('s_articles');
        }

        foreach ($article as $field => $value) {
            $key = $this->mapFieldName($field, 'Shopware\Models\Article\Article');
            if (!$key) {
                continue;
            }

            $type = $this->guessType($value, $field, 'Shopware\Models\Article\Article');
            $value = $builder->createNamedParameter(
                empty($value) && $type == \PDO::PARAM_NULL ? "NULL" : $value,
                $type
            );
            if ($articleId) {
                $builder->set($key, $value);
            } else {
                $builder->setValue($key, $value);
            }
        }

        return $builder;
    }

    protected function guessType($value, $key, $class)
    {
        $metaData = Shopware()->Models()->getClassMetadata($class);
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

        $nullable = $metaData->fieldMappings[$key]['nullable'];

        if (empty($value) && $nullable) {
            return \PDO::PARAM_NULL;
        }

        if (!array_key_exists($metaData->fieldMappings[$key]['type'], $pdoTypeMapping)) {
            throw new \RuntimeException("Type {$metaData->fieldMappings[$key]['type']} not found");
        }

        return $pdoTypeMapping[$metaData->fieldMappings[$key]['type']];
    }

    protected function mapFieldName($key, $entity)
    {
        $metaData = Shopware()->Models()->getClassMetadata($entity);
        if (!array_key_exists($key, $metaData->fieldMappings)) {
            return false;
        }

        return $metaData->fieldMappings[$key]['columnName'];
    }
}