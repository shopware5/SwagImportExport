<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DbAdapters\Products;

use Doctrine\DBAL\Connection;
use Shopware\Models\Article\Article;
use Shopware\Models\Article\Detail;
use Shopware\Models\Attribute\Article as ProductAttribute;
use SwagImportExport\Components\DataManagers\Products\ProductDataManager;
use SwagImportExport\Components\DataType\ProductDataType;
use SwagImportExport\Components\DbAdapters\Results\ProductWriterResult;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;
use SwagImportExport\Components\Validators\Products\ProductValidator;

class ProductWriter
{
    private const MAIN_KIND = 1;
    private const VARIANT_KIND = 2;

    protected \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    protected Connection $connection;

    protected ProductValidator $validator;

    protected ProductDataManager $dataManager;

    private DbalHelper $dbalHelper;

    public function __construct(
        Connection $connection,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        DbalHelper $dbalHelper,
        ProductDataManager $productDataManager
    ) {
        $this->validator = new ProductValidator();
        $this->connection = $connection;
        $this->db = $db;
        $this->dbalHelper = $dbalHelper;
        $this->dataManager = $productDataManager;
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $defaultValues
     *
     * @throws AdapterException
     */
    public function write(array $product, array $defaultValues): ProductWriterResult
    {
        $product = $this->validator->filterEmptyString($product);
        $this->validator->checkRequiredFields($product);

        return $this->insertOrUpdateProduct($product, $defaultValues);
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $defaultValues
     *
     * @throws AdapterException
     */
    protected function insertOrUpdateProduct(array $product, array $defaultValues): ProductWriterResult
    {
        $shouldCreateMainProduct = false;
        [$mainVariantId, $productId, $variantId] = $this->findExistingEntries($product);

        if ($product['processed']) {
            if (!$mainVariantId) {
                $mainVariantId = $variantId;
            }

            return new ProductWriterResult($productId, $variantId, $mainVariantId);
        }

        $createDetail = $variantId === 0;

        // if detail needs to be created and the (different) mainDetail does not exist: error
        if ($createDetail && !$mainVariantId && !$this->isMainDetail($product)) {
            $message = SnippetsHelper::getNamespace()
                ->get('adapters/articles/variant_existence', 'Variant with number %s does not exist.');
            throw new AdapterException(sprintf($message, $product['mainNumber']));
        }

        // Set create flag
        if ($createDetail && $this->isMainDetail($product)) {
            $shouldCreateMainProduct = true;
            $product = $this->dataManager->setDefaultFieldsForCreate($product, $defaultValues);
            $this->validator->checkRequiredFieldsForCreate($product);
        }

        $product = $this->dataManager->setDefaultFields($product);
        $this->validator->validate($product, ProductDataType::$mapper);
        $product = $this->dataManager->setProductData($product, ProductDataType::$productFieldsMapping);

        // insert/update main detail product
        if ($this->isMainDetail($product)) {
            $productId = $this->createOrUpdateMainVariant($product, $shouldCreateMainProduct, $productId);
        }

        $product['articleId'] = $productId;
        if (empty($product['kind'])) {
            $product['kind'] = $mainVariantId === $variantId ? self::MAIN_KIND : self::VARIANT_KIND;
        }
        [$product, $variantId] = $this->createOrUpdateProductVariant($product, $defaultValues, $variantId, $createDetail);

        // set reference
        if ($shouldCreateMainProduct) {
            $this->db->query('UPDATE s_articles SET main_detail_id = ? WHERE id = ?', [$variantId, $productId]);
        }

        // insert attributes
        $this->createProductAttributes($product, $productId, $variantId, $shouldCreateMainProduct);

        if (!$mainVariantId) {
            $mainVariantId = $variantId;
        }

        return new ProductWriterResult($productId, $mainVariantId, $variantId);
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected function findExistingEntries(array $product): array
    {
        $productId = 0;
        $mainVariantId = 0;
        $variantId = 0;

        // Try to find an existing main variant
        if ($product['mainNumber']) {
            $result = $this->db->fetchRow(
                'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
                $product['mainNumber']
            );

            if (!empty($result)) {
                $mainVariantId = (int) $result['id'];
                $productId = (int) $result['articleID'];
            }
        }

        // try to find the existing detail
        $result = $this->db->fetchRow(
            'SELECT ad.id, ad.articleID FROM s_articles_details ad WHERE ordernumber = ?',
            [$product['orderNumber']]
        );

        if (!empty($result)) {
            $variantId = (int) $result['id'];
            $productId = (int) $result['articleID'];
        }

        return [$mainVariantId, $productId, $variantId];
    }

    /**
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    protected function mapProductAttributes(array $product): array
    {
        $attributes = [];
        foreach ($product as $key => $value) {
            $position = strpos($key, 'attribute');
            if ($position !== 0) {
                continue;
            }

            $attrKey = lcfirst(str_replace('attribute', '', $key));
            $attributes[$attrKey] = $value;
        }

        return $attributes;
    }

    protected function getAttrId(int $detailId): int
    {
        $sql = 'SELECT id FROM s_articles_attributes WHERE articledetailsID = ?';

        return (int) $this->connection->fetchOne($sql, [$detailId]);
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
            $shouldCreateMainProduct ? null : $productId
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
        $attributes = $this->mapProductAttributes($product);
        $attributes['articleId'] = $productId;
        $attributes['articleDetailId'] = $variantId;

        $builder = $this->dbalHelper->getQueryBuilderForEntity(
            $attributes,
            ProductAttribute::class,
            $createProduct ? null : $this->getAttrId($variantId)
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
        $product = $this->dataManager->setProductVariantData($product, ProductDataType::$productVariantFieldsMapping);

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
