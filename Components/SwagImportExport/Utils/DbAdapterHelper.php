<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DbAdapterHelper
{
    static public function decodeHtmlEntities($records)
    {
        foreach ($records as &$record) {
            foreach ($record as &$value) {
                $value = html_entity_decode($value, ENT_COMPAT | ENT_HTML401, "UTF-8");
            }
        }
        
        return $records;
    }
}
