<?php

namespace Shopware\Components\SwagImportExport\DataManagers;

class ArticlePriceDataManager
{
    /** Define which field should be set by default */
    private $defaultFields = array(
        'priceGroup',
        'from',
    );

    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
            if (isset($record[$key])) {
                continue;
            }

            switch ($key) {
                case 'priceGroup':
                    $record['priceGroup'] = $this->getPriceGroup();
                    break;
                case 'from':
                    $record['from'] = $this->getFrom();
                    break;
            }
        }

        return $record;
    }

    private function getPriceGroup()
    {
        return 'EK';
    }

    private function getFrom()
    {
        return 1;
    }
}