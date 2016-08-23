<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DataManagers;

use Shopware\Components\SwagImportExport\DataType\CustomerDataType;

class CustomerDataManager extends DataManager
{
    /**
     * @var \Shopware_Components_Config
     */
    private $config = null;

    /**
     * @var \Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db = null;

    /**
     * @var \Shopware\Components\Password\Manager
     */
    private $passwordManager = null;

    /**
     * initialises the class properties
     */
    public function __construct()
    {
        $this->db = Shopware()->Db();
        $this->config = Shopware()->Config();
        $this->passwordManager = Shopware()->PasswordEncoder();
    }

    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return CustomerDataType::$defaultFieldsForCreate;
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
     * Sets fields which are empty by default.
     *
     * @param $record
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
                case 'paymentID':
                    if (!$record[$key]) {
                        $record[$key] = $this->getPayment($record);
                    }
                    break;
                case 'encoder':
                    if (!$record[$key]) {
                        $record[$key] = $this->getEncoder();
                    }
                    break;
                case 'attrBillingText1':
                    $record[$key] = '';
                    break;
                case 'attrBillingText2':
                    $record[$key] = '';
                    break;
                case 'attrBillingText3':
                    $record[$key] = '';
                    break;
                case 'attrBillingText4':
                    $record[$key] = '';
                    break;
                case 'attrBillingText5':
                    $record[$key] = '';
                    break;
                case 'attrBillingText6':
                    $record[$key] = '';
                    break;
                case 'attrShippingText1':
                    $record[$key] = '';
                    break;
                case 'attrShippingText2':
                    $record[$key] = '';
                    break;
                case 'attrShippingText3':
                    $record[$key] = '';
                    break;
                case 'attrShippingText4':
                    $record[$key] = '';
                    break;
                case 'attrShippingText5':
                    $record[$key] = '';
                    break;
                case 'attrShippingText6':
                    $record[$key] = '';
                    break;
            }
        }

        $record = $this->fixDefaultValues($record);

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
        $query = "SELECT `value`.value
                  FROM s_core_config_elements AS element
                  JOIN s_core_config_values AS `value` ON `value`.element_id = element.id
                  WHERE `value`.shop_id = ? AND element.name = 'defaultpayment'";

        return $this->db->fetchOne($query, array($subShopId));
    }

    /**
     * @param int $subShopId
     * @return string
     */
    private function getMainShopDefaultPaymentId($subShopId)
    {
        $query = "SELECT `value`.value
                  FROM s_core_config_elements AS element
                  JOIN s_core_config_values AS `value` ON `value`.element_id = element.id
                  WHERE `value`.shop_id = (SELECT main_id FROM s_core_shops WHERE id = ?)
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

    /**
     * Return proper values for customer fields which have values NULL
     *
     * @param array $records
     * @return array
     */
    public function fixDefaultValues($records)
    {
        $defaultFieldsValues = CustomerDataType::$defaultFieldsValues;
        $records = $this->fixFieldsValues($records, $defaultFieldsValues);

        return $records;
    }
}
