<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Utils;

class DataColumnOptions
{
    private $columnOptions;

    /**
     * @param $columnOptions
     */
    public function __construct($columnOptions)
    {
        $this->columnOptions = $columnOptions;
    }

    /**
     * @return mixed
     */
    public function getColumnOptions()
    {
        return $this->columnOptions;
    }
}
