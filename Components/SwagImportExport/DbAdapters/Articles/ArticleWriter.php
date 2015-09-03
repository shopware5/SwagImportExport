<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\Articles\ArticleValidator;
use Shopware\Components\SwagImportExport\DataManagers\Articles\ArticleDataManager;

class ArticleWriter
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    protected $dbalHelper;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var ArticleValidator */
    protected $validator;

    /** @var ArticleDataManager */
    protected $dataManager;

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->dbalHelper = new DbalHelper();

        $this->validator = new ArticleValidator();
        $this->dataManager = new ArticleDataManager($this->db, $this->dbalHelper);
    }

    public function write($article)
    {
        $article = $this->validator->prepareInitialData($article);
        $this->validator->checkRequiredFields($article);

        return $this->insertOrUpdateArticle($article);
    }

    protected function insertOrUpdateArticle($article)
    {
        list($mainDetailId, $articleId, $detailId) = $this->findExistingEntries($article);

        if ($article['processed']) {
            return array($articleId, $detailId, $mainDetailId ? : $detailId);
        }

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
            $article = $this->dataManager->setDefaultFieldsForCreate($article);
            $this->validator->checkRequiredFieldsForCreate($article);
        }

        $article = $this->dataManager->setDefaultFields($article);

        // insert article
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $article,
            'Shopware\Models\Article\Article',
            $createArticle ? false : $articleId
        );
        $builder->execute();
        if ($createArticle) {
            $articleId = $this->connection->lastInsertId();
        }

        // insert detail
        $article['number'] = $article['orderNumber'];
        $article['articleId'] = $articleId;
        $article['kind'] = $mainDetailId == $detailId ? 1 : 2;
        $builder = $this->dbalHelper->getQueryBuilderForEntity($article, 'Shopware\Models\Article\Detail', $detailId);
        $builder->execute();

        if (!$detailId) {
            $detailId = $this->connection->lastInsertId();
        }

        // set reference
        if ($createArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', array($detailId, $articleId));
        }

        // insert attributes
        $attributes = $this->mapArticleAttributes($article);
        $attributes['articleId'] = $articleId;
        $attributes['articleDetailId'] = $detailId;
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $attributes,
            'Shopware\Models\Attribute\Article',
            $createArticle ? false : $this->getAttrId($detailId)
        );
        $builder->execute();

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

    protected function mapArticleAttributes($article)
    {
        $attributes = array();
        foreach($article as $key => $value) {
            $position = strpos($key, 'attribute');
            if ($position === false || $position !== 0) {
                continue;
            }

            $attrKey = lcfirst(str_replace('attribute', '', $key));
            $attributes[$attrKey] = $value;
        }

        return $attributes;
    }

    protected function getAttrId($detailId)
    {
        $sql = "SELECT id FROM s_articles_attributes WHERE articledetailsID = ?";
        $attrId = $this->connection->fetchColumn($sql, array($detailId));

        return $attrId;
    }
}