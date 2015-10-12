<?php
namespace Shopware\Components\SwagImportExport\DataManagers;

class CategoriesDataManager
{
    /** Define which field should be set by default */
    private static $defaultFields = array(
        'parentId',
        'template',
        'active',
        'showFilterGroups',
        'attributeAttribute1',
        'attributeAttribute2',
        'attributeAttribute3',
        'attributeAttribute4',
        'attributeAttribute5',
        'attributeAttribute6'
    );

    /**
     * Return fields which should be set by default
     *
     * @return array
     */
    public function getDefaultFields()
    {
        return self::$defaultFields;
    }

    /**
     * Sets fields which are empty by default.
     *
     * @param $record
     * @param array $defaultValues
     * @return mixed
     */
    public function setDefaultFields($record, $defaultValues)
    {
        $getDefaultFields = $this->getDefaultFields();
        foreach ($getDefaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            if (isset($defaultValues[$key])) {
                $record[$key] = $defaultValues[$key];
            }
        }

        return $record;
    }
}