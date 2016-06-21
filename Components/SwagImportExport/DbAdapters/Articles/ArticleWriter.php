<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Enlight_Components_Db_Adapter_Pdo_Mysql as PDOConnection;
use Shopware\Components\SwagImportExport\DataType\ArticleDataType;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\Articles\ArticleValidator;
use Shopware\Components\SwagImportExport\DataManagers\Articles\ArticleDataManager;

class ArticleWriter
{
    /**
     * @var PDOConnection $db
     */
    protected $db;


    /**
     * @var DbalHelper $dbalHelper
     */
    private $dbalHelper;

    /**
     * @var Connection $connection
     */
    protected $connection;

    /**
     * @var ArticleValidator
     */
    protected $validator;

    /**
     * @var ArticleDataManager
     */
    protected $dataManager;

    /**
     * initialises the class properties
     */
    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
        $this->db = Shopware()->Db();
        $this->dbalHelper = new DbalHelper();

        $this->validator = new ArticleValidator();
        $this->dataManager = new ArticleDataManager($this->db, $this->dbalHelper);
    }

    /**
     * @param array $article
     * @param array $defaultValues
     * @return array
     * @throws AdapterException
     */
    public function write($article, $defaultValues)
    {
        $article = $this->validator->prepareInitialData($article);
        $this->validator->checkRequiredFields($article);

        return $this->insertOrUpdateArticle($article, $defaultValues);
    }

    /**
     * @param array $article
     * @param array $defaultValues
     * @return array
     * @throws AdapterException
     */
    protected function insertOrUpdateArticle($article, $defaultValues)
    {
        list($mainDetailId, $articleId, $detailId) = $this->findExistingEntries($article);

        if ($article['processed']) {
            return array($articleId, $detailId, $mainDetailId ?: $detailId);
        }

        $createDetail = $detailId == 0;

        // if detail needs to be created and the (different) mainDetail does not exist: error
        if ($createDetail && !$mainDetailId && $article['mainNumber'] != $article['orderNumber']) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/variant_existence', 'Variant with number %s does not exists.');
            throw new AdapterException(sprintf($message, $article['mainNumber']));
        }

        // Set create flag
        $createArticle = false;
        if ($createDetail && $article['mainNumber'] == $article['orderNumber']) {
            $createArticle = true;
            $article = $this->dataManager->setDefaultFieldsForCreate($article, $defaultValues);
            $this->validator->checkRequiredFieldsForCreate($article);
        }

        $article = $this->dataManager->setDefaultFields($article);
        $this->validator->validate($article, ArticleDataType::$mapper);
        $article = $this->dataManager->setArticleData($article, ArticleDataType::$articleFieldsMapping);

        Shopware()->Events()->notify('Shopware_Plugins_SwagImportExport_UpdateArticle', new \Enlight_Event_EventArgs(array(
            'article' => $article
        )));

        // insert/update article
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $article,
            'Shopware\Models\Article\Article',
            $createArticle ? false : $articleId
        );
        $builder->execute();
        if ($createArticle) {
            $articleId = $this->connection->lastInsertId();
        }

        // insert/update detail
        $article = $this->dataManager->setArticleVariantData($article, ArticleDataType::$articleVariantFieldsMapping);
        $article['articleId'] = $articleId;
        $article['kind'] = $mainDetailId == $detailId ? 1 : 2;

        if ($createDetail) {
            $article = $this->dataManager->setDefaultFieldsForCreate($article, $defaultValues);
        }

        $builder = $this->dbalHelper->getQueryBuilderForEntity($article, 'Shopware\Models\Article\Detail', $detailId);
        $builder->execute();

        if (!$detailId) {
            $detailId = $this->connection->lastInsertId();
        }

        // set reference
        if ($createArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', array($detailId, $articleId));
        }

        // set value to active column in s_articles table
        $active = $this->getActiveForArticlesTable($article, $articleId);
        if ($createArticle === false && $active !== null) {
            $this->db->query('UPDATE s_articles SET active = ? WHERE id = ?', array($active, $articleId));
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

        return array($articleId, $detailId, $mainDetailId ?: $detailId);
    }

    /**
     * @param array $article
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

    /**
     * Returns the value for `active` column needed for `s_articles` table.
     *
     * @param int $articleId
     * @param array $article
     * @return int|null
     */
    protected function getActiveForArticlesTable($article, $articleId)
    {
        // if active data is missing from the profile tree or is not set in the file - continue
        if ($article['active'] === null || $article['active'] === '') {
            return null;
        }

        // if there is at least one detail which is active, set active column as 1 for s_articles table
        $sql = 'SELECT active
                FROM s_articles_details
                WHERE articleID = ?
                ORDER BY active DESC LIMIT 1';

        return (int) $this->db->fetchOne($sql, $articleId);
    }

    /**
     * @param array $article
     * @return array
     */
    protected function mapArticleAttributes($article)
    {
        $attributes = array();
        foreach ($article as $key => $value) {
            $position = strpos($key, 'attribute');
            if ($position === false || $position !== 0) {
                continue;
            }

            $attrKey = lcfirst(str_replace('attribute', '', $key));
            $attributes[$attrKey] = $value;
        }

        return $attributes;
    }

    /**
     * @param int $detailId
     * @return string|bool
     */
    protected function getAttrId($detailId)
    {
        $sql = "SELECT id FROM s_articles_attributes WHERE articledetailsID = ?";
        $attrId = $this->connection->fetchColumn($sql, array($detailId));

        return $attrId;
    }
}
