<?php

namespace Shopware\Components\SwagImportExport\DataManagers\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\DataManagers\DataManager;
use Shopware\Components\SwagImportExport\DataType\ArticleDataType;

class ArticleDataManager extends DataManager
{
    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db = null;

    /**
     * @var DbalHelper
     */
    private $dbalHelper = null;

    private $taxRates = null;

    private $suppliers = null;

    /**
     * @param \Enlight_Components_Db_Adapter_Pdo_Mysql $db
     * @param DbalHelper $dbalHelper
     */
    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db, DbalHelper $dbalHelper)
    {
        $this->db = $db;
        $this->dbalHelper = $dbalHelper;
    }

    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return ArticleDataType::$defaultFieldsForCreate;
    }

    /**
     * Return fields which should be set by default
     *
     * @return array
     */
    public function getDefaultFieldsName()
    {
        $defaultFieldsForCreate = $this->getDefaultFields();
        $defaultFields = $this->getFields($defaultFieldsForCreate);

        return $defaultFields;
    }

    /**
     * Returns available taxes.
     *
     * @return array
     */
    private function getTaxRates()
    {
        if ($this->taxRates === null) {
            $this->taxRates = $this->prepareTaxRates();
        }

        return $this->taxRates;
    }

    /**
     * Return list with all shop taxes
     *
     * @return array
     */
    private function prepareTaxRates()
    {
        $taxes = $this->db->fetchPairs('SELECT id, tax FROM s_core_tax');
        if (!is_array($taxes)) {
            return array();
        }
        ksort($taxes);

        return $taxes;
    }

    /**
     * Returns available suppliers.
     *
     * @return array
     */
    private function getSuppliers()
    {
        if ($this->suppliers === null) {
            $this->suppliers = $this->prepareSuppliers();
        }

        return $this->suppliers;
    }

    /**
     * Return list with suppliers
     *
     * @return array
     */
    private function prepareSuppliers()
    {
        $suppliers = $this->db->fetchPairs('SELECT name, id FROM s_articles_supplier');
        if (!is_array($suppliers)) {
            return array();
        }

        return $suppliers;
    }

    /**
     * Sets fields which are empty, but we need them to create new entry.
     *
     * @param array $record
     * @param array $defaultValues
     * @return mixed
     */
    public function setDefaultFieldsForCreate($record, $defaultValues)
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
                    $record[$key] = date('Y-m-d');
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
                case 'attributeAttr17':
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

        $record = $this->fixDefaultValues($record);

        return $record;
    }

    /**
     * Get valid tax id depending on tax id or tax rate field.
     *
     * @param array $record
     * @return bool|mixed
     * @throws AdapterException
     */
    private function getTaxId($record)
    {
        $taxes = $this->getTaxRates();

        $taxIds = array_keys($taxes);

        if (isset($record['taxId']) && in_array($record['taxId'], $taxIds)) {
            return $record['taxId'];
        } elseif (isset($record['tax'])) {
            $taxId = $this->getTaxByTaxRate($record['tax'], $record['orderNumber']);

            return $taxId;
        }

        return false;
    }

    /**
     * @param float $taxRate
     * @param string $orderNumber
     * @return mixed
     * @throws AdapterException
     */
    private function getTaxByTaxRate($taxRate, $orderNumber)
    {
        $taxRate = number_format($taxRate, 2);

        $taxId = array_search($taxRate, $this->getTaxRates());

        if (!$taxId) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/no_tax_found',
                "Tax by tax rate %s not found for article %s."
            );
            throw new AdapterException(sprintf($message, $taxRate, $orderNumber));
        }

        return $taxId;
    }

    /**
     * @param array $record
     * @return string
     */
    private function getSupplierId($record)
    {
        $this->suppliers = $this->getSuppliers();
        $name = $record['supplierName'];
        $supplierId = $this->suppliers[$name];

        //creates supplier if does not exists
        if (!$supplierId) {
            $data = array('name' => $name);
            $builder = $this->dbalHelper->getQueryBuilderForEntity(
                $data,
                'Shopware\Models\Article\Supplier',
                false
            );
            $builder->execute();
            $supplierId = $this->db->lastInsertId();
            $this->suppliers[$name] = $supplierId;
        }

        return $supplierId;
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param array $record
     * @return mixed
     * @throws AdapterException
     */
    public function setDefaultFields($record)
    {
        $getDefaultFields = $this->getDefaultFieldsName();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'taxId':
                    if (isset($record['tax'])) {
                        $record[$key] = $this->getTaxByTaxRate($record['tax'], $record['orderNumber']);
                    }
                    break;
                case 'supplierId':
                    if (isset($record['supplierName'])) {
                        $record[$key] = $this->getSupplierId($record);
                    }
                    break;
                case 'added':
                    $record[$key] = date('Y-m-d');
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
                case 'attributeAttr17':
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

        return $record;
    }

    /**
     * Return proper values for article fields which have values NULL
     *
     * @param array $records
     * @return array
     */
    public function fixDefaultValues($records)
    {
        $defaultFieldsValues = ArticleDataType::$defaultFieldsValues;
        $records = $this->fixFieldsValues($records, $defaultFieldsValues);

        return $records;
    }

    /**
     * Update article records which are missing because
     * doctrine property and database mismatch
     *
     * @param array $record
     * @param array $mapping
     * @return array
     */
    public function setArticleData($record, $mapping)
    {
        $record = $this->mapFields($record, $mapping);

        return $record;
    }

    /**
     * Update article variant records which are missing because
     * doctrine property and database name mismatch
     *
     * @param array $record
     * @param array $mapping
     * @return array
     */
    public function setArticleVariantData($record, $mapping)
    {
        $record = $this->mapFields($record, $mapping);

        return $record;
    }

}
