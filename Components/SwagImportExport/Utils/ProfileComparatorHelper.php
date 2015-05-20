<?php

namespace Shopware\Components\SwagImportExport\Utils;

class ProfileComparatorHelper
{
    static public function getRequiredUpdateNodesByName($name)
    {
        $returnValue = array();

        switch($name) {
            case 'categories':
                $returnValue = array('categoryId', 'parentId');
                break;
            case 'articles':
                $returnValue = array('orderNumber', 'mainNumber', 'unitId', 'priceGroupId', 'configSetId');
                break;
            case 'articlesInStock':
            case 'articlesPrices':
                $returnValue = array('orderNumber');
                break;
            case 'articlesImages':
                $returnValue = array('ordernumber', 'image');
                break;
            case 'articlesTranslations':
                $returnValue = array('articleNumber', 'languageId');
                break;
            case 'orders':
                $returnValue = array('orderId');
                break;
            case 'customers':
                $returnValue = array('customerNumber', 'ustid', 'billingCountryID', 'paymentID', 'subshopID');
                break;
            case 'newsletter':
                $returnValue = array('email', 'userID');
                break;
            case 'translations':
                $returnValue = array('objectKey', 'objectType', 'languageId');
                break;
            default:
                break;
        }

        return $returnValue;
    }
}