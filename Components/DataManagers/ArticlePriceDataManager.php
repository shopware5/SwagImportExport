<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\DataManagers;

class ArticlePriceDataManager implements \Enlight_Hook
{
    /**
     * Define which field should be set by default
     *
     * @var array<string>
     */
    private array $defaultFields = [
        'priceGroup',
        'from',
        'to',
        'percent',
    ];

    /**
     * Sets fields which are empty by default.
     *
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    public function setDefaultFields(array $record): array
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
                case 'to':
                    $record[$key] = 'beliebig';
                    break;
                case 'percent':
                    $record[$key] = 0.0;
                    break;
            }
        }

        return $record;
    }
}
