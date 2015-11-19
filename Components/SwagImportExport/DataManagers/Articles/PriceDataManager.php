<?php

namespace Shopware\Components\SwagImportExport\DataManagers\Articles;

use Shopware\Components\SwagImportExport\DataType\ArticlePriceDataType;
use Shopware\Components\SwagImportExport\DataManagers\DataManager;

class PriceDataManager extends DataManager
{
    /**
     * Define which field should be set by default
     *
     * @var array
     */
    private $defaultFields = array(
        'priceGroup',
        'from',
        'to',
    );

    /**
     * Sets fields which are empty by default.
     *
     * @param array $record
     * @return mixed
     */
    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
            switch ($key) {
                case 'priceGroup':
                    $record[$key] = empty($record[$key]) ? 'EK' : $record[$key];
                    break;
                case 'from':
                    $record[$key] = empty($record[$key]) ? 1 : intval($record[$key]);
                    break;
                case 'to':
                    $record[$key] = $this->getTo($record[$key]);
                    break;
            }
        }

        return $record;
    }

    /**
     * @param $to
     * @return int|string
     */
    private function getTo($to)
    {
        $to = !empty($to) ? intval($to) : 0;

        // if the "to" value isn't numeric, set the place holder 'beliebig'
        if ($to <= 0) {
            $to = 'beliebig';
        }

        return $to;
    }

    /**
     * Return proper values for article price fields which have values NULL
     *
     * @param array $records
     * @return array
     */
    public function fixDefaultValues($records)
    {
        $defaultFieldsValues = ArticlePriceDataType::$defaultFieldsValues;
        $records = $this->fixFieldsValues($records, $defaultFieldsValues);

        return $records;
    }
}
