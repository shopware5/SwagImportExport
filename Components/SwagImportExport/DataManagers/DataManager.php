<?php

namespace Shopware\Components\SwagImportExport\DataManagers;

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
        foreach($defaultFields as $type => $fields) {
            foreach($fields as $field) {
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
        foreach($mapper as $type => $fields) {
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
}