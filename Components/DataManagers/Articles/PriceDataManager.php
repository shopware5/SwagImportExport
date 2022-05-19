<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers\Articles;

use SwagImportExport\Components\DataManagers\DataManager;
use SwagImportExport\Components\DataType\ArticlePriceDataType;

class PriceDataManager extends DataManager implements \Enlight_Hook
{
    /**
     * Define which field should be set by default
     *
     * @var array
     */
    private $defaultFields = [
        'priceGroup',
        'from',
        'to',
    ];

    /**
     * Sets fields which are empty by default.
     *
     * @param array $record
     */
    public function setDefaultFields($record)
    {
        foreach ($this->defaultFields as $key) {
            switch ($key) {
                case 'priceGroup':
                    $record[$key] = empty($record[$key]) ? 'EK' : $record[$key];
                    break;
                case 'from':
                    $record[$key] = empty($record[$key]) ? 1 : (int) $record[$key];
                    break;
                case 'to':
                    $record[$key] = $this->getTo($record[$key]);
                    break;
            }
        }

        return $record;
    }

    /**
     * Return proper values for article price fields which have values NULL
     *
     * @param array $records
     *
     * @return array
     */
    public function fixDefaultValues($records)
    {
        $defaultFieldsValues = ArticlePriceDataType::$defaultFieldsValues;

        return $this->fixFieldsValues($records, $defaultFieldsValues);
    }

    /**
     * @return int|string
     */
    private function getTo($to)
    {
        $to = !empty($to) ? (int) $to : 0;

        // if the "to" value isn't numeric, set the place holder 'beliebig'
        if ($to <= 0) {
            $to = 'beliebig';
        }

        return $to;
    }
}
