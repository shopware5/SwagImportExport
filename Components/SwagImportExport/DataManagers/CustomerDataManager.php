<?php

namespace Shopware\Components\SwagImportExport\DataManagers;

class CustomerDataManager
{
    /** @var \Shopware_Components_Config */
    private $config = null;

    /** @var \Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db = null;

    /** @var \Shopware\Components\Password\Manager */
    private $passwordManager = null;

    /** Define which field should be set by default */
    private $defaultFields = array(
        'active',
        'paymentID',
        'encoder',
        'subshopID',
    );

    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->config = Shopware()->Config();
        $this->passwordManager = Shopware()->PasswordEncoder();
    }

    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'active':
                    $record['active'] = $this->getActive();
                    break;
                case 'paymentID':
                    $record['paymentID'] = $this->getPayment($record);
                    break;
                case 'encoder':
                    $record['encoder'] = $this->getEncoder();
                    break;
                case 'subshopID':
                    $record['subshopID'] = $this->getSubShopId();
                    break;
            }
        }

        return $record;
    }

    private function getActive()
    {
        return 1;
    }

    private function getPayment($record)
    {
        if (!isset($record['subshopID'])) {
            return $this->config->get('sDEFAULTPAYMENT');
        }

        $subShopId = $record['subshopID'];

        //get defaultPaymentId for subShop
        $defaultPaymentId = $this->getSubShopDefaultPaymentId($subShopId);
        if ($defaultPaymentId) {
            return unserialize($defaultPaymentId);
        }

        //get defaultPaymentId for mainShop
        $defaultPaymentId = $this->getMainShopDefaultPaymentId($subShopId);
        if ($defaultPaymentId) {
            return unserialize($defaultPaymentId);
        }

        return $this->config->get('sDEFAULTPAYMENT');
    }

    private function getSubShopDefaultPaymentId($subShopId)
    {
        $query = "SELECT value.value
                  FROM s_core_config_elements AS element
                  JOIN s_core_config_values AS value ON value.element_id = element.id
                  WHERE value.shop_id = ? AND element.name = 'defaultpayment'";

        return $this->db->fetchOne($query, array($subShopId));
    }

    private function getMainShopDefaultPaymentId($subShopId)
    {
        $query =  "SELECT value.value
                   FROM s_core_config_elements AS element
                   JOIN s_core_config_values AS value ON value.element_id = element.id
                   WHERE value.shop_id = (SELECT main_id FROM s_core_shops WHERE id = ?)
                         AND element.name = 'defaultpayment'";

        return $this->db->fetchOne($query, array($subShopId));
    }

    private function getEncoder()
    {
        return $this->passwordManager->getDefaultPasswordEncoderName();
    }

    private function getSubShopId()
    {
        return 1;
    }
}