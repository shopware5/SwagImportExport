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
            }
        }

        $record['taxId'] = $this->getTaxId($record);

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
            }
        }

        return $record;
    }
}
