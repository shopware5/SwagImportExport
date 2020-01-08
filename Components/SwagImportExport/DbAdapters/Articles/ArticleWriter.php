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
     * @param array<string, mixed> $article
     * @param array<string, mixed> $defaultValues
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
     * @param array<string, mixed> $article
     * @param array<string, mixed> $defaultValues
     *
     * @throws AdapterException
     *
     * @return ArticleWriterResult
     */
    protected function insertOrUpdateArticle($article, $defaultValues)
    {
        $shouldCreateMainArticle = false;
        list($mainVariantId, $productId, $variantId) = $this->findExistingEntries($article);

        if ($article['processed']) {
            if (!$mainVariantId) {
                $mainVariantId = $variantId;
            }

            return new ArticleWriterResult($productId, $variantId, $mainVariantId);
        }

        $createDetail = $variantId == 0;

        // if detail needs to be created and the (different) mainDetail does not exist: error
        if ($createDetail && !$mainVariantId && !$this->isMainDetail($article)) {
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
            $productId = $this->createOrUpdateMainVariant($article, $shouldCreateMainArticle, $productId);
        }

        $article['articleId'] = $productId;
        if (!isset($article['kind']) || empty($article['kind'])) {
            $article['kind'] = $mainVariantId == $variantId ? 1 : 2;
        }else {
            $this->db->query('
            UPDATE
                s_articles a
                LEFT JOIN s_articles_details d ON d.id = a.main_detail_id
            SET
                a.main_detail_id = (
                    SELECT
                        id
                    FROM
                        s_articles_details
                    WHERE
                        articleID = a.id
                        AND kind = 1
                    LIMIT 1)
            WHERE a.id = ?
            ', [$productId]);
        }
        list($article, $variantId) = $this->createOrUpdateProductVariant($article, $defaultValues, $variantId, $createDetail);

        // set reference
        if ($shouldCreateMainArticle) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', [$variantId, $productId]);
        }

        // insert attributes
        $this->createProductAttributes($article, $productId, $variantId, $shouldCreateMainArticle);

        if (!$mainVariantId) {
            $mainVariantId = $variantId;
        }

        return new ArticleWriterResult($productId, $mainVariantId, $variantId);
    }

    /**
     * @param array<string, mixed> $article
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected function findExistingEntries($article)
    {
        $productId = 0;
        $mainVariantId = 0;
        $variantId = 0;

        // Try to find an existing main variant
        if ($article['mainNumber']) {
            $result = $this->db->fetchRow(
                'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
                $article['mainNumber']
            );
            if (!empty($result)) {
                $mainVariantId = (int) $result['id'];
                $productId = (int) $result['articleID'];
            }
        }

        // try to find the existing detail
        $result = $this->db->fetchRow(
            'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
            [$article['orderNumber']]
        );
        if (!empty($result)) {
            $variantId = (int) $result['id'];
            $productId = (int) $result['articleID'];
        }

        return [$mainVariantId, $productId, $variantId];
    }

    /**
     * @param array<string, mixed> $article
     *
     * @return array<string, mixed>
     */
    protected function mapArticleAttributes($article)
    {
        $attributes = [];
        foreach ($article as $key => $value) {
            $position = \strpos($key, 'attribute');
            if ($position !== 0) {
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
     * @return int
     */
    protected function getAttrId($detailId)
    {
        $sql = 'SELECT id FROM s_articles_attributes WHERE articledetailsID = ?';

        return (int) $this->connection->fetchColumn($sql, [$detailId]);
    }

    /**
     * @param array<string, mixed> $product
     */
    private function isMainDetail(array $product): bool
    {
        return $product['mainNumber'] === $product['orderNumber'];
    }

    /**
     * @param array<string, mixed> $product
     */
    private function createOrUpdateMainVariant(array $product, bool $shouldCreateMainProduct, int $productId): int
    {
        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $product,
            Article::class,
            $shouldCreateMainProduct ? false : $productId
        );
        $builder->execute();

        if ($shouldCreateMainProduct) {
            return (int) $this->connection->lastInsertId();
        }

        return $productId;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function createProductAttributes(array $product, int $productId, int $variantId, bool $createProduct): void
    {
        $attributes = $this->mapArticleAttributes($product);
        $attributes['articleId'] = $productId;
        $attributes['articleDetailId'] = $variantId;

        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $attributes,
            ProductAttribute::class,
            $createProduct ? false : $this->getAttrId($variantId)
        );
        $builder->execute();
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $defaultValues
     *
     * @return array{0: array<string, mixed>, 1: int}
     */
    private function createOrUpdateProductVariant(array $product, array $defaultValues, int $variantId, bool $createVariant): array
    {
        $product = $this->dataManager->setArticleVariantData($product, ArticleDataType::$articleVariantFieldsMapping);

        if ($createVariant) {
            $product = $this->dataManager->setDefaultFieldsForCreate($product, $defaultValues);
        }

        $builder = $this->dbalHelper->getQueryBuilderForEntity($product, Detail::class, $variantId);
        $builder->execute();

        if ($variantId === 0) {
            $variantId = (int) $this->connection->lastInsertId();
        }

        return [$product, $variantId];
    }
}
