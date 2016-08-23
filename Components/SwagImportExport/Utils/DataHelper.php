<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Utils;

class DataHelper
{
    /**
     * @param $column
     * @return array
     */
    public static function generateMappingFromColumns($column)
    {
        preg_match('/(?<=as ).*/', $column, $alias);
        $alias = trim($alias[0]);

        preg_match("/(?<=\.).*?(?= as)/", $column, $name);
        $name = trim($name[0]);

        return array($alias, $name);
    }

    /**
     * @param $bytes
     * @return string
     */
    public static function formatFileSize($bytes)
    {
        if ($bytes > 0) {
            $unit = intval(log($bytes, 1024));

            $units = array('B', 'KB', 'MB', 'GB');

            if (array_key_exists($unit, $units) === true) {
                return sprintf('%s %s', number_format($bytes / pow(1024, $unit), 2), $units[$unit]);
            }
        }

        return $bytes;
    }
}
