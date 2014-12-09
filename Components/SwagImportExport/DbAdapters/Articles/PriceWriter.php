<?php

namespace Shopware\Components\SwagImportExport\DbAdapters\Articles;

use Shopware\Components\SwagImportExport\Exception\AdapterException;
use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;

class PriceWriter
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = Shopware()->Models()->getConnection();
    }

    public function write($articleId, $articleDetailId, $prices)
    {
        foreach ($prices as $price) {
            $price = $this->setDefaultValues($price);
            $this->checkCustomerGroup($price['priceGroup']);


        }
    }

    protected function checkPriceData()
    {

        if (!isset($priceData['price']) && empty($priceData['price'])) {
            $message = SnippetsHelper::getNamespace()->get(
                    'adapters/articles/incorrect_price',
                    'Price value is incorrect for article with nubmer %s'
                );
            throw new AdapterException(sprintf($message . $variant->getNumber()));
        }
    }


    protected function checkCustomerGroup($group)
    {
        $result = $this->connection->fetchArray(
            'SELECT id FROM s_core_customergroups WHERE groupkey = ?',
            array($group)
        );
        if (empty($result)) {
            $message = SnippetsHelper::getNamespace()->get(
                'adapters/customerGroup_not_found',
                'Customer Group by key %s not found for article %s'
            );
            throw new AdapterException(sprintf($message, $group, ''));
        }
    }

    /**
     * @param $price
     */
    protected function setDefaultValues($price)
    {
        if (empty($price['priceGroup'])) {
            $price['priceGroup'] = 'EK';
        }

        if (!isset($price['from'])) {
            $price['from'] = 1;
        }

        $price['from'] = intval($price['from']);

        if (isset($price['to'])) {
            $price['to'] = intval($price['to']);
        } else {
            $price['to'] = 0;
        }

        // if the "to" value isn't numeric, set the place holder "beliebig"
        if ($price['to'] <= 0) {
            $price['to'] = 'beliebig';
        }

        return $price;
    }
}