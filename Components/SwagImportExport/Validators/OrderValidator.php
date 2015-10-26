<?php

namespace Shopware\Components\SwagImportExport\Validators;

use Shopware\Components\SwagImportExport\Utils\SnippetsHelper;
use Shopware\Components\SwagImportExport\Exception\AdapterException;

class OrderValidator extends Validator
{
    public static $mapper = array(
        'int' => array(
            'orderId',
            'customerId',
            'status',
            'cleared',
            'paymentId',
            'dispatchId',
            'shopId',
            'net',
            'taxFree',
            'orderDetailId',
            'articleId',
            'taxId',
            'statusId',
            'quantity',
            'shipped',
            'shippedGroup',
            'mode',
            'esd'
        ),
        'string' => array( //TODO: maybe we don't need to check fields which contains string?
            'number',
            'comment',
            'transactionId',
            'partnerId',
            'customerComment',
            'internalComment',
            'temporaryId',
            'referer',
            'trackingCode',
            'languageIso',
            'currency',
            'remoteAddress',
            'articleNumber',
            'articleName',
            'config'
        ),
        'float' => array(
            'invoiceAmount',
            'invoiceAmountNet',
            'invoiceShipping',
            'invoiceShippingNet',
            'currencyFactor',
            'taxRate',
            'price'
        ),
        'dateTime' => array('orderTime', 'clearedDate', 'releasedate'),
    );

    //TODO: check which other fields are required
    private $requiredFields = array(
        array('orderId', 'number', 'orderDetailId'), //one of these fields must be set
    );

    private $snippetData = array(
        'orderId' => array(
            'adapters/orders/ordernumber_order_details_requires',
            'Order number or order detail id must be provided'
        ),
    );

    /**
     * Checks whether required fields are filled-in
     *
     * @param array $record
     * @throws AdapterException
     */
    public function checkRequiredFields($record)
    {
        foreach ($this->requiredFields as $key) {
            if (is_array($key)) {
                list($orderId, $number, $orderDetailId) = $key;

                if (isset($record[$orderId]) || isset($record[$number]) || isset($record[$orderDetailId])) {
                    continue;
                }
                $key = $orderId;
            } else if (isset($record[$key])) {
                continue;
            }

            list($snippetName, $snippetMessage) = $this->snippetData[$key];

            $message = SnippetsHelper::getNamespace()->get($snippetName, $snippetMessage);
            throw new AdapterException($message);
        }
    }
}