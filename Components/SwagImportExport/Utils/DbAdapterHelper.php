<?php

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
}
