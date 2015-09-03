<?php

namespace Shopware\Components\SwagImportExport\DataManagers\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class ArticleDataManager
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db = null;

    /** @var DbalHelper */
    private $dbalHelper = null;

    private $taxRates = array();

    private $suppliers = array();

    /** Define which field should be set by default */
    private $defaultFields = array(
        'taxId',
        'supplierId',
    );

    private $defaultFieldsForCreate = array(
        'taxId',
        'supplierId',
    );

    public function __construct(\Enlight_Components_Db_Adapter_Pdo_Mysql $db, DbalHelper $dbalHelper)
    {
        $this->db = $db;
        $this->dbalHelper = $dbalHelper;
        $this->taxRates = $this->getTaxRates();
        $this->suppliers = $this->getSuppliers();
    }

    private function getTaxRates()
    {
        $taxes = $this->db->fetchPairs('SELECT id, tax FROM s_core_tax');
        if (!is_array($taxes)) {
            return array();
        }
        ksort($taxes);

        return $taxes;
    }

    private function getSuppliers()
    {
        $suppliers = $this->db->fetchPairs('SELECT name, id FROM s_articles_supplier');
        if (!is_array($suppliers)) {
            return array();
        }

        return $suppliers;
    }

    public function setDefaultFieldsForCreate($record)
    {
        foreach ($this->defaultFieldsForCreate as $key) {
            if (isset($record[$key])) {
                continue;
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

        return $record;
    }

    private function getTaxId($record)
    {
        $taxId = isset($record['tax']) ? $this->getTaxByTaxRate($record['tax'], $record['orderNumber']) : $this->getTaxByDefault();

        return $taxId;
    }

    private function getTaxByTaxRate($taxRate, $orderNumber)
    {
        $taxRate = number_format($taxRate, 2);

        $taxId = array_search($taxRate, $this->taxRates);

        if (!$taxId) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/no_tax_found',
                "Tax by tax rate %s not found for article %s."
            );
            throw new AdapterException(sprintf($message, $taxRate, $orderNumber));
        }

        return $taxId;
    }

    private function getTaxByDefault()
    {
        // todo: read default tax rate from config
        return array_shift(array_keys($this->taxRates));
    }

    private function getSupplierId($record)
    {
        $name = $record['supplierName'];
        $supplierId = $this->suppliers[$name];

        //creates supplier if does not exists
        if (!$supplierId) {
            $data = array('name' => $name);
            $builder =  $this->dbalHelper->getQueryBuilderForEntity(
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

    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
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