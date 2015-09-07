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

    /**
     * Sets fields which are empty by default.
     *
     * @param $record
     * @return mixed
     */
    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'active':
                    $record[$key] = 1;
                    break;
                case 'paymentID':
                    $record[$key] = $this->getPayment($record);
                    break;
                case 'encoder':
                    $record[$key] = $this->getEncoder();
                    break;
                case 'subshopID':
                    $record[$key] = 1;
                    break;
            }
        }

        return $record;
    }

    /**
     * @param array $record
     * @return mixed
     */
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

    /**
     * @param int $subShopId
     * @return string
     */
    private function getSubShopDefaultPaymentId($subShopId)
    {
        $query = "SELECT value.value
                  FROM s_core_config_elements AS element
                  JOIN s_core_config_values AS value ON value.element_id = element.id
                  WHERE value.shop_id = ? AND element.name = 'defaultpayment'";

        return $this->db->fetchOne($query, array($subShopId));
    }

    /**
     * @param int $subShopId
     * @return string
     */
    private function getMainShopDefaultPaymentId($subShopId)
    {
        $query =  "SELECT value.value
                   FROM s_core_config_elements AS element
                   JOIN s_core_config_values AS value ON value.element_id = element.id
                   WHERE value.shop_id = (SELECT main_id FROM s_core_shops WHERE id = ?)
                         AND element.name = 'defaultpayment'";

        return $this->db->fetchOne($query, array($subShopId));
    }

    /**
     * @return string
     */
    private function getEncoder()
    {
        return $this->passwordManager->getDefaultPasswordEncoderName();
    }
}