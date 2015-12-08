<?php

namespace Shopware\Components\SwagImportExport\DataManagers;

class ArticlePriceDataManager
{
    /**
     * Define which field should be set by default
     *
     * @var array
     */
    private $defaultFields = array(
        'priceGroup',
        'from',
        'basePrice',
        'percent'
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
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'priceGroup':
                    $record[$key] = 'EK';
                    break;
                case 'from':
                    $record[$key] = 1;
                    break;
                case 'basePrice':
                    $record[$key] = 0.0;
                    break;
                case 'percent':
                    $record[$key] = 0.0;
                    break;
            }
        }

        return $record;
    }
}
