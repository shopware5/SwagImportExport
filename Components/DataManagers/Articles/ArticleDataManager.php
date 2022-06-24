<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers\Articles;

use Shopware\Models\Article\Supplier;
use SwagImportExport\Components\DataManagers\DataManager;
use SwagImportExport\Components\DataType\ArticleDataType;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\DbalHelper;
use SwagImportExport\Components\Exception\AdapterException;
use SwagImportExport\Components\Utils\SnippetsHelper;

class ArticleDataManager extends DataManager implements \Enlight_Hook
{
    private \Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    private DbalHelper $dbalHelper;

    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db, DbalHelper $dbalHelper)
    {
        $this->db = $db;
        $this->dbalHelper = $dbalHelper;
    }

    public function supports(string $managerType): bool
    {
        return $managerType === DataDbAdapter::ARTICLE_ADAPTER;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getDefaultFields(): array
    {
        return ArticleDataType::$defaultFieldsForCreate;
    }

    /**
     * Return fields which should be set by default
     *
     * @return array<string>
     */
    public function getDefaultFieldsName(): array
    {
        $defaultFieldsForCreate = $this->getDefaultFields();

        return $this->getFields($defaultFieldsForCreate);
    }

    /**
     * Sets fields which are empty, but we need them to create new entry.
     *
     * @param array<string, string|int> $record
     * @param array<string, mixed>      $defaultValues
     *
     * @return array<string, mixed>
     */
    public function setDefaultFieldsForCreate(array $record, array $defaultValues): array
    {
        $getDefaultFields = $this->getDefaultFieldsName();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            if (isset($defaultValues[$key])) {
                $record[$key] = $defaultValues[$key];
            }

            switch ($key) {
                case 'taxId':
                    $record[$key] = $this->getTaxId($record);
                    break;
                case 'supplierId':
                    if (isset($record['supplierName'])) {
                        $record[$key] = $this->getSupplierId($record);
                    }
                    break;
                case 'added':
                    $record[$key] = \date('Y-m-d');
                    break;
                case 'description':
                    $record[$key] = '';
                    break;
                case 'descriptionLong':
                    $record[$key] = '';
                    break;
                case 'metaTitle':
                    $record[$key] = '';
                    break;
                case 'keywords':
                    $record[$key] = '';
                    break;
                case 'shippingTime':
                    $record[$key] = '';
                    break;
                case 'supplierNumber':
                    $record[$key] = '';
                    break;
                case 'additionalText':
                    $record[$key] = '';
                    break;
                case 'ean':
                    $record[$key] = '';
                    break;
                case 'packUnit':
                    $record[$key] = '';
                    break;
                case 'inStock':
                    $record[$key] = 0;
                    break;
                case 'stockMin':
                    $record[$key] = 0;
                    break;
                case 'weight':
                    $record[$key] = 0.0;
                    break;
                case 'attributeAttr1':
                    $record[$key] = '';
                    break;
                case 'attributeAttr2':
                    $record[$key] = '';
                    break;
                case 'attributeAttr3':
                    $record[$key] = '';
                    break;
                case 'attributeAttr4':
                    $record[$key] = '';
                    break;
                case 'attributeAttr5':
                    $record[$key] = '';
                    break;
                case 'attributeAttr6':
                    $record[$key] = '';
                    break;
                case 'attributeAttr7':
                    $record[$key] = '';
                    break;
                case 'attributeAttr8':
                    $record[$key] = '';
                    break;
                case 'attributeAttr9':
                    $record[$key] = '';
                    break;
                case 'attributeAttr10':
                    $record[$key] = '';
                    break;
                case 'attributeAttr11':
                    $record[$key] = '';
                    break;
                case 'attributeAttr12':
                    $record[$key] = '';
                    break;
                case 'attributeAttr13':
                    $record[$key] = '';
                    break;
                case 'attributeAttr14':
                    $record[$key] = '';
                    break;
                case 'attributeAttr15':
                    $record[$key] = '';
                    break;
                case 'attributeAttr16':
                    $record[$key] = '';
                    break;
                case 'attributeAttr18':
                    $record[$key] = '';
                    break;
                case 'attributeAttr19':
                    $record[$key] = '';
                    break;
                case 'attributeAttr20':
                    $record[$key] = '';
                    break;
            }
        }

        $record['taxId'] = $this->getTaxId($record);

        return $this->fixDefaultValues($record);
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function setDefaultFields(array $record): array
    {
        $getDefaultFields = $this->getDefaultFieldsName();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'taxId':
                    if (isset($record['tax'])) {
                        $record[$key] = $this->getTaxByTaxRate((float) $record['tax'], $record['orderNumber']);
                    }
                    break;
                case 'supplierId':
                    if (isset($record['supplierName'])) {
                        $record[$key] = $this->getSupplierId($record);
                    }
                    break;
            }
        }

        return $record;
    }

    /**
     * Return proper values for article fields which have values NULL
     *
     * @param array<string, int|string> $records
     *
     * @return array<string, mixed>
     */
    public function fixDefaultValues(array $records): array
    {
        $defaultFieldsValues = ArticleDataType::$defaultFieldsValues;

        return $this->fixFieldsValues($records, $defaultFieldsValues);
    }

    /**
     * Update article records which are missing because
     * doctrine property and database mismatch
     *
     * @param array<string, string|int> $record
     * @param array<string, string|int> $mapping
     *
     * @return array<string, mixed>
     */
    public function setArticleData(array $record, array $mapping): array
    {
        return $this->mapFields($record, $mapping);
    }

    /**
     * Update article variant records which are missing because
     * doctrine property and database name mismatch
     *
     * @param array<string, string|int> $record
     * @param array<string, string|int> $mapping
     *
     * @return array<string, mixed>
     */
    public function setArticleVariantData(array $record, array $mapping): array
    {
        return $this->mapFields($record, $mapping);
    }

    /**
     * Returns available taxes.
     *
     * @return array<int, int>
     */
    private function getTaxRates(): array
    {
        $taxes = $this->db->fetchPairs('SELECT id, tax FROM s_core_tax');
        if (!\is_array($taxes)) {
            return [];
        }
        \ksort($taxes);

        return $taxes;
    }

    /**
     * Returns available suppliers.
     *
     * @return array<int, string>
     */
    private function getSuppliers(): array
    {
        $suppliers = $this->db->fetchPairs('SELECT name, id FROM s_articles_supplier');
        if (!\is_array($suppliers)) {
            return [];
        }

        return $suppliers;
    }

    /**
     * Get valid tax id depending on tax id or tax rate field.
     *
     * @param array<string, string|int> $record
     *
     * @throws AdapterException
     *
     * @return ?int
     */
    private function getTaxId(array $record): ?int
    {
        $taxes = $this->getTaxRates();

        $taxIds = \array_keys($taxes);

        if (isset($record['taxId']) && \in_array($record['taxId'], $taxIds)) {
            return (int) $record['taxId'];
        }

        if (isset($record['tax'])) {
            return $this->getTaxByTaxRate((float) $record['tax'], $record['orderNumber']);
        }

        return null;
    }

    /**
     * @throws AdapterException
     */
    private function getTaxByTaxRate(float $taxRate, string $orderNumber): int
    {
        $taxRate = \number_format($taxRate, 2);

        $taxId = \array_search($taxRate, $this->getTaxRates());

        if (!$taxId) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/no_tax_found',
                'Tax by tax rate %s not found for article %s.'
            );
            throw new AdapterException(\sprintf($message, $taxRate, $orderNumber));
        }

        return (int) $taxId;
    }

    /**
     * @param array<string, string|int> $record
     */
    private function getSupplierId(array $record): int
    {
        $suppliers = $this->getSuppliers();
        $name = $record['supplierName'];
        $supplierId = $suppliers[$name];

        // creates supplier if does not exists
        if (!$supplierId) {
            $data = ['name' => $name];
            $builder = $this->dbalHelper->getQueryBuilderForEntity(
                $data,
                Supplier::class,
                null
            );
            $builder->execute();
            $supplierId = $this->db->lastInsertId();
            $suppliers[$name] = $supplierId;
        }

        return (int) $supplierId;
    }
}
