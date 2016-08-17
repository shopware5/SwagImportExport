<?php

/*
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\DataManagers;

/**
 * Class DataManager
 *
 * @package Shopware\Components\SwagImportExport\DataManagers
 */
class DataManager
{
    /**
     * Return fields which should be set by default
     *
     * @param array $defaultFields Contains default fields name and types
     * @return array
     */
    public function getFields($defaultFields)
    {
        $defaultValues = array();
        foreach ($defaultFields as $type => $fields) {
            foreach ($fields as $field) {
                $defaultValues[] = $field;
            }
        }

        return $defaultValues;
    }

    /**
     * Return type of default field
     *
     * @param array $record
     * @param array $mapper
     * @return bool|int|string
     */
    public static function getFieldType($record, $mapper)
    {
        foreach ($mapper as $type => $fields) {
            if (in_array($record, $fields)) {
                return $type;
            }
        }

        return false;
    }

    /**
     * Cast default value to it proper type
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    public static function castDefaultValue($value, $type)
    {
        switch ($type) {
            case 'id':
            case 'integer':
                return (int) $value;
                break;
            case 'boolean':
                return ($value == 'true') ? 1 : 0;
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * Return proper values for fields which have values NULL
     *
     * @param array $records
     * @param array $fieldsValues
     * @return array
     */
    public function fixFieldsValues($records, $fieldsValues)
    {
        foreach ($fieldsValues as $type => $fields) {
            foreach ($fields as $field) {
                if (empty($records[$field])) {
                    switch ($type) {
                        case 'string':
                            $records[$field] = '';
                            break;
                        case 'int':
                            $records[$field] = '0';
                            break;
                        case 'float':
                            $records[$field] = '0.0';
                            break;
                        case 'date':
                            $records[$field] = date('Y-m-d', time());
                    }
                }
            }
        }

        return $records;
    }

    /**
     * Add columns which are missing because
     * doctrine property and database mismatch
     *
     * @param array $records
     * @param array $adapterFields
     * @return array
     */
    public function mapFields($records, $adapterFields)
    {
        foreach ($adapterFields as $tableField => $adapterField) {
            if (isset($records[$adapterField])) {
                $records[$tableField] = $records[$adapterField];
            }
        }

        return $records;
    }
}
