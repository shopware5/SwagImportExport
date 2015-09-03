<?php

namespace Shopware\Components\SwagImportExport\Validators\Articles;

use Shopware\Components\SwagImportExport\Validators\Validator;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class PriceValidator extends Validator
{
    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db = null;

    private $detailId = null;

    private $requiredFields = array(
        array('price', 'priceGroup'),
    );

    private $snippetData = array(
        'price' => array(
            'adapters/articles/incorrect_price',
            'Price value is incorrect for article with number %s',
        ),
    );

    public function __construct()
    {
        $this->db = Shopware()->Db();
    }

    private function getOrderNumber()
    {
        $sql = "SELECT ordernumber FROM s_articles_details WHERE id = ?";
        $orderNumber = Shopware()->Db()->fetchOne($sql, array($this->detailId));

        return $orderNumber;
    }

    public function setDetailId($detailId)
    {
        $this->detailId = $detailId;
    }

    public function checkRequiredFields($record)
    {
        $orderNumber = $this->getOrderNumber();
        foreach ($this->requiredFields as $key) {
            list($price, $priceGroup) = $key;
            if (!empty($record[$price]) || $record[$priceGroup] !== 'EK') {
                continue;
            }

            $key = $price;

            list($snippetName, $snippetMessage) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException(sprintf($message, $orderNumber));
        }
    }

    public function validate($record, $customerGroups)
    {
        $orderNumber = $this->getOrderNumber();
        if (!array_key_exists($record['priceGroup'], $customerGroups)) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/customerGroup_not_found',
                'Customer Group by key %s not found for article %s'
            );
            throw new AdapterException(sprintf($message, $record['priceGroup'], $orderNumber));
        }

        if ($record['from'] <= 0) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/articles/invalid_price',
                'Invalid Price "from" value for article %s'
            );
            throw new AdapterException(sprintf($message, $orderNumber));
        }
    }
}