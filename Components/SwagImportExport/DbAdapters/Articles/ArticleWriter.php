<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use Shopware\Components\SwagImportExport\DataManagers\Articles\ArticleDataManager;
use Shopware\Components\SwagImportExport\DataType\ArticleDataType;
use Shopware\Components\SwagImportExport\DbAdapters\Results\ArticleWriterResult;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\Articles\ArticleValidator;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Article as ProductAttribute;

class ArticleWriter
{
    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    protected $db;

    /**
     * @var Connection
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
     * @var DbalHelper
     */
    private $dbalHelper;

    /**
     * initialises the class properties
     */
    public function __construct()
    {
        $this->connection = Shopware()->Container()->get('dbal_connection');
        $this->db = Shopware()->Container()->get('db');
        $this->dbalHelper = DbalHelper::create();

        $this->validator = new ArticleValidator();
        $this->dataManager = new ArticleDataManager($this->db, $this->dbalHelper);
    }

    /**
     * @param array $article
     * @param array $defaultValues
     *
     * @throws AdapterException
     *
     * @return ArticleWriterResult
     */
    public function write($article, $defaultValues)
    {
        $article = $this->validator->filterEmptyString($article);
        $this->validator->checkRequiredFields($article);

        return $this->insertOrUpdateArticle($article, $defaultValues);
    }

    /**
     * @param array $article
     * @param array $defaultValues
     *
     * @throws AdapterException
     *
     * @return ArticleWriterResult
     */
    protected function insertOrUpdateArticle($article, $defaultValues)
    {
        $shouldCreateMainArticle = false;
        list($mainDetailId, $articleId, $detailId) = $this->findExistingEntries($article);

        if ($article['processed']) {
            if (!$mainDetailId) {
                $mainDetailId = $detailId;
            }

            return new ArticleWriterResult($articleId, $detailId, $mainDetailId);
        }

        $createDetail = $detailId == 0;

        // if detail needs to be created and the (different) mainDetail does not exist: error
        if ($createDetail && !$mainDetailId && !$this->isMainDetail($article)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/variant_existence', 'Variant with number %s does not exists.');
            throw new AdapterException(\sprintf($message, $article['mainNumber']));
        }

        // Set create flag
        if ($createDetail && $this->isMainDetail($article)) {
            $shouldCreateMainArticle = true;
            $article = $this->dataManager->setDefaultFieldsForCreate($article, $defaultValues);
            $this->validator->checkRequiredFieldsForCreate($article);
        }

        $article = $this->dataManager->setDefaultFields($article);
        $this->validator->validate($article, ArticleDataType::$mapper);
        $article = $this->dataManager->setArticleData($article, ArticleDataType::$articleFieldsMapping);

        // insert/update main detail article
        if ($this->isMainDetail($article)) {
            $articleId = $this->createOrUpdateMainDetail($article, $shouldCreateMainArticle, $articleId);
        }

        $article['articleId'] = $articleId;
        $article['kind'] = $mainDetailId == $detailId ? 1 : 2;
        list($article, $detailId) = $this->createOrUpdateArticleDetail($article, $defaultValues, $detailId, $createDetail);

        // set reference
        if ($shouldCreateMainArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', [$detailId, $articleId]);
        }

        // insert attributes
        $this->createArticleAttributes($article, $articleId, $detailId, $shouldCreateMainArticle);

        if (!$mainDetailId) {
            $mainDetailId = $detailId;
        }

        return new ArticleWriterResult($articleId, $mainDetailId, $detailId);
    }

    /**
     * @param array $article
     *
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
            [$article['orderNumber']]
        );
        if (!empty($result)) {
            $detailId = $result['id'];
            $articleId = $result['articleID'];
        }

        return [$mainDetailId, $articleId, $detailId];
    }

    /**
     * @param array $article
     *
     * @return array
     */
    protected function mapArticleAttributes($article)
    {
        $attributes = [];
        foreach ($article as $key => $value) {
            $position = \strpos($key, 'attribute');
            if ($position === false || $position !== 0) {
                continue;
            }

            $attrKey = \lcfirst(\str_replace('attribute', '', $key));
            $attributes[$attrKey] = $value;
        }

        return $attributes;
    }

    /**
     * @param int $detailId
     *
     * @return string|bool
     */
    protected function getAttrId($detailId)
    {
        $sql = 'SELECT id FROM s_articles_attributes WHERE articledetailsID = ?';
        $attrId = $this->connection->fetchColumn($sql, [$detailId]);

        return $attrId;
    }

    /**
     * @return bool
     */
    private function isMainDetail(array $article)
    {
        return $article['mainNumber'] == $article['orderNumber'];
    }

    /**
     * @param array $article
     * @param bool  $shouldCreateMainArticle
     * @param int   $articleId
     *
     * @return int
     */
    private function createOrUpdateMainDetail($article, $shouldCreateMainArticle, $articleId)
    {
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $article,
            Article::class,
            $shouldCreateMainArticle ? false : $articleId
        );
        $builder->execute();

        if ($shouldCreateMainArticle) {
            return $this->connection->lastInsertId();
        }

        return $articleId;
    }

    /**
     * @param array $article
     * @param int   $articleId
     * @param int   $detailId
     * @param bool  $createArticle
     */
    private function createArticleAttributes($article, $articleId, $detailId, $createArticle)
    {
        $attributes = $this->mapArticleAttributes($article);
        $attributes['articleId'] = $articleId;
        $attributes['articleDetailId'] = $detailId;

        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $attributes,
            ProductAttribute::class,
            $createArticle ? false : $this->getAttrId($detailId)
        );
        $builder->execute();
    }

    /**
     * @param array $article
     * @param array $defaultValues
     * @param int   $detailId
     * @param bool  $createDetail
     *
     * @return array
     */
    private function createOrUpdateArticleDetail($article, $defaultValues, $detailId, $createDetail)
    {
        $article = $this->dataManager->setArticleVariantData($article, ArticleDataType::$articleVariantFieldsMapping);

        if ($createDetail) {
            $article = $this->dataManager->setDefaultFieldsForCreate($article, $defaultValues);
        }

        $builder = $this->dbalHelper->getQueryBuilderForEntity($article, Detail::class, $detailId);
        $builder->execute();

        if (!$detailId) {
            $detailId = $this->connection->lastInsertId();
        }

        return [$article, $detailId];
    }
}
