<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Validators\Articles\PriceValidator;
use Shopware\Components\SwagImportExport\DataManagers\Articles\PriceDataManager;

class PriceWriter
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    protected $db;

    protected $customerGroups;

    /** @var PriceValidator */
    protected $validator;

    /** @var PriceDataManager */
    protected $dataManager;

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->dbalHelper = new DbalHelper();
        $this->validator = new PriceValidator();
        $this->dataManager = new PriceDataManager();

        $this->customerGroups = $this->getCustomerGroup();
    }

    public function write($articleId, $articleDetailId, $prices)
    {
        $tax = $this->getArticleTaxRate($articleId);

        foreach ($prices as $price) {
            $price = $this->validator->prepareInitialData($price);
            $price = $this->dataManager->setDefaultFields($price);
            $this->validator->checkRequiredFields($price);
            $this->validator->validate($price, PriceValidator::$mapper);

            $this->checkRequirements($price, $articleDetailId);

            // skip empty prices for non-default customer groups
            if (empty($price['price']) && $price['priceGroup'] !== 'EK') {
                continue;
            }

            $priceId = $this->db->fetchOne(
                '
                    SELECT id
                    FROM s_articles_prices
                    WHERE articleID = ? AND articledetailsID = ? AND pricegroup = ? AND `from` = ?
                ',
                array($articleId, $articleDetailId, $price['priceGroup'], $price['from'])
            );

            $newPrice = $priceId == 0;
            $price['articleId'] = $articleId;
            $price['articleDetailsId'] = $articleDetailId;
            $price['customerGroupKey'] = $price['priceGroup'];
            $price = $this->calculatePrice($price, $newPrice, $tax);
            $builder = $this->dbalHelper->getQueryBuilderForEntity($price, 'Shopware\Models\Article\Price', $priceId);
            $builder->execute();
        }
    }

    protected function calculatePrice($price, $newPrice, $tax)
    {
        $taxInput = $this->customerGroups[$price['priceGroup']];

        $price['price'] = floatval(str_replace(",", ".", $price['price']));

        if (isset($price['basePrice'])) {
            $price['basePrice'] = floatval(str_replace(",", ".", $price['basePrice']));
        }

        if (isset($price['pseudoPrice'])) {
            $price['pseudoPrice'] = floatval(str_replace(",", ".", $price['pseudoPrice']));
        } else {
            if ($newPrice) {
                $price['pseudoPrice'] = 0;
            }

            if ($taxInput) {
                $price['pseudoPrice'] = round($price['pseudoPrice'] * (100 + $tax) / 100, 2);
            }
        }

        if (isset($price['percent'])) {
            $price['percent'] = floatval(str_replace(",", ".", $price['percent']));
        }

        if ($taxInput) {
            $price['price'] = $price['price'] / (100 + $tax) * 100;
            $price['pseudoPrice'] = $price['pseudoPrice'] / (100 + $tax) * 100;
        }

        return $price;
    }

    protected function checkRequirements($price, $articleDetailId)
    {
        if (!array_key_exists($price['priceGroup'], $this->customerGroups)) {
            $orderNumber = $this->getArticleOrderNumber($articleDetailId);
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/customerGroup_not_found',
                'Customer Group by key %s not found for article %s'
            );
            throw new AdapterException(sprintf($message, $price['priceGroup'], $orderNumber));
        }

        if ((!isset($price['price']) || empty($price['price'])) && $price['priceGroup'] == 'EK') {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/incorrect_price',
                'Price value is incorrect for article with nubmer %s'
            );
            throw new AdapterException(sprintf($message, ''));
        }

        if ($price['from'] <= 0) {
            $orderNumber = $this->getArticleOrderNumber($articleDetailId);
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/invalid_price',
                'Invalid Price "from" value for article %s'
            );
            throw new AdapterException(sprintf($message, $orderNumber));
        }
    }

    private function getCustomerGroup()
    {
        $groups = $this->db->fetchPairs(
            'SELECT groupkey, taxinput FROM s_core_customergroups'
        );

        return $groups;
    }

    /**
     * @param $articleId
     * @return float
     * @throws AdapterException
     */
    protected function getArticleTaxRate($articleId)
    {
        $sql = 'SELECT coretax.tax FROM s_core_tax AS coretax
                LEFT JOIN s_articles AS article ON article.taxID = coretax.id
                WHERE article.id = ?';
        $tax = $this->db->fetchOne($sql, array($articleId));

        if (empty($tax)) {
            throw new AdapterException("Tax for article $articleId not found");
        }

        return floatval($tax);
    }

    /**
     * @param int $articleDetailId
     * @return string
     */
    protected function getArticleOrderNumber($articleDetailId)
    {
        $sql = 'SELECT ordernumber FROM s_articles_details WHERE id = ?';
        $orderNumber = $this->db->fetchOne($sql, array($articleDetailId));

        return $orderNumber;
    }
}
