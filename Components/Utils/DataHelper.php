<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

class DataHelper
{
    /**
     * This function strips sql commands from the
     * string like "as" to get a "cleaned" value for the column
     * and the in the query used alias.
     *
     * example: 'customer.number as customerNumber' results in ['customerNumber', 'number']
     *
     * @return array
     */
    public static function generateMappingFromColumns($column)
    {
        if (!\preg_match('/(?<=as ).*/', $column, $alias)) {
            return [];
        }
        $alias = \trim($alias[0]);

        \preg_match("/(?<=\.).*?(?= as|\W)/", $column, $name);
        $name = \trim($name[0]);

        return [$alias, $name];
    }

    public static function formatFileSize($bytes): string
    {
        if ($bytes > 0) {
            $unit = (int) \log($bytes, 1024);

            $units = ['B', 'KB', 'MB', 'GB'];

            if (\array_key_exists($unit, $units) === true) {
                return \sprintf('%s %s', \number_format($bytes / \pow(1024, $unit), 2), $units[$unit]);
            }
        }

        return $bytes;
    }
}
