<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\ORM\Mapping\ClassMetadata;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\QueryBuilder\QueryBuilder;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class ArticleWriter
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->dbalHelper = new DbalHelper();
    }

    public function write($article)
    {
        if (!isset($article['orderNumber']) && empty($article['orderNumber'])) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/ordernumber_required',
                'Order number is required.'
            );
            throw new AdapterException($message);
        }
        return $this->insertOrUpdateArticle($article);
    }

    protected function insertOrUpdateArticle($article)
    {
        list($mainDetailId, $articleId, $detailId) = $this->findExistingEntries($article);

        $createDetail = $detailId == 0;

        // if detail needs to be created and the (different) mainDetail does not exist: error
        if ($createDetail && !$mainDetailId && $article['mainNumber'] != $article['orderNumber']) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/variant_existence',
                'Variant with number %s does not exists.'
            );
            throw new AdapterException(sprintf($message, $article['mainNumber']));
        }

        // Set create flag
        $createArticle = false;
        if ($createDetail && $article['mainNumber'] == $article['orderNumber']) {
            $createArticle = true;
        }

        // insert article
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $article,
            'Shopware\Models\Article\Article',
            $createArticle ? false : $articleId
        );
        $builder->execute();
        $articleId = $this->db->lastInsertId();

        // insert detail
        $article['number'] = $article['orderNumber'];
        $article['articleId'] = $articleId;
        $article['kind'] = $createArticle ? 1 : 2;
        $builder = $this->dbalHelper->getQueryBuilderForEntity($article, 'Shopware\Models\Article\Detail', $detailId);
        $builder->execute();

        if (!$detailId) {
            $detailId = $this->db->lastInsertId();
        }

        // set reference
        if ($createArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', array($detailId, $articleId));
        }

        return array($articleId, $detailId, $mainDetailId ? : $detailId);
    }

    /**
     * @param $article
     * @return array
     */
    protected function findExistingEntries($article)
    {
        $articleId = null;
        $mainDetailId = null;
        $detailId = null;

        // Try to find an existing main variant
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

        // try to find the existing detail
        $result = $this->db->fetchRow(
            'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
            array($article['orderNumber'])
        );
        if (!empty($result)) {
            $detailId = $result['id'];
            $articleId = $result['articleID'];
        }

        return array($mainDetailId, $articleId, $detailId);
    }
}