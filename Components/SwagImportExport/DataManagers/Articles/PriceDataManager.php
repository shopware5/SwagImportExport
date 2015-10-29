<?php

namespace Shopware\Components\SwagImportExport\DataManagers\Articles;

class PriceDataManager
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
}
