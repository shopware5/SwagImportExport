<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Utils;

class DbAdapterHelper
{
    /**
     * @param $records
     * @return mixed
     */
    public static function decodeHtmlEntities($records)
    {
        foreach ($records as &$record) {
            foreach ($record as &$value) {
                $value = self::convertBooleanFalseToString($value);

                if (!is_array($value)) {
                    $value = html_entity_decode($value, ENT_COMPAT | ENT_HTML401, "UTF-8");
                }
            }
        }

        return $records;
    }

    /**
     * @param $records
     * @return mixed
     */
    public static function escapeNewLines($records)
    {
        foreach ($records as &$record) {
            foreach ($record as &$value) {
                $value = str_replace(array("\n", "\r", "\r\n", "\n\r"), ' ', $value);
            }
        }

        return $records;
    }

    /**
     * html_entity_encode would return an empty string if boolean false is passed.
     *
     * @param mixed $value
     * @return mixed|string
     */
    private static function convertBooleanFalseToString($value)
    {
        if (false === $value) {
            return "0";
        }
        return $value;
    }
}
