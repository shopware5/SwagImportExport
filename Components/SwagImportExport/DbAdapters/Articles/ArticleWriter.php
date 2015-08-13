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

        $this->taxRates = $this->getTaxRates();
        $this->suppliers = $this->getSuppliers();
    }

    public function write($article)
    {
        if (!isset($article['orderNumber']) || empty($article['orderNumber'])) {
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
        }

        $article = $this->setDefaultValues($article, $createArticle);

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

    protected function setDefaultValues($article, $create)
    {
        $orderNumber = $article['orderNumber'];
        if ($create && empty($article['taxId']) && empty($article['tax'])) {
            // todo: read default tax rate from config
            $article['taxId'] = array_shift(array_keys($this->taxRates));
        }

        if (isset($article['taxId']) && $article['taxId'] !== '') {
            $article['tax'] = $this->taxRates[$article['taxId']];
        } else if (isset($article['tax']) && $article['tax'] !== '') {
            $tax = number_format($article['tax'], 2);
            $article['taxId'] = $this->getTax($tax, $orderNumber);
        }

        if (!isset($article['supplierId']) && isset($article['supplierName'])){
            $article['supplierId'] = $this->getSupplier($article['supplierName'], $orderNumber);
        }

        if ($create && empty($article['supplierId'])){
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/supplier_not_found',
                "Supplier not found for article %s."
            );
            throw new AdapterException(sprintf($message, $orderNumber));
        }

        return $article;
    }

    protected function getTaxRates()
    {
        $tax = $this->db->fetchPairs('SELECT id, tax FROM s_core_tax');
        if (!is_array($tax)) {
            return array();
        }
        ksort($tax);

        return $tax;
    }

    protected function getSuppliers()
    {
        $suppliers = array();
        $result = $this->connection->fetchAll('SELECT `id`, `name` FROM s_articles_supplier');

        foreach ($result as $row) {
            $suppliers[$row['name']] = $row['id'];
        }

        return $suppliers;
    }

    /**
     * Returns the taxes rate ID
     *
     * @param $taxRate
     * @param $orderNumber
     * @return int
     * @throws AdapterException
     */
    public function getTax($taxRate, $orderNumber)
    {
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

    /**
     * Returns the supplier ID
     *
     * @param $name
     * @return int
     * @throws AdapterException
     */
    public function getSupplier($name){

        $supplierId = $this->suppliers[$name];

        //creates supplier if does not exists
        if (!$supplierId){
            $data = array('name' => $name);
            $builder =  $this->dbalHelper->getQueryBuilderForEntity(
                $data,
                'Shopware\Models\Article\Supplier',
                false
            );
            $builder->execute();
            $supplierId = $this->connection->lastInsertId();
            $this->suppliers[$name] = $supplierId;
        }

        return $supplierId;
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