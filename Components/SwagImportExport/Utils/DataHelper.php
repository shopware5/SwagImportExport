<?php

namespace Shopware\Components\SwagImportExport\Utils;

class DataHelper
{
    public static function generateMappingFromColumns($column)
    {
        preg_match('/(?<=as ).*/', $column, $alias);
        $alias = trim($alias[0]);

        preg_match("/(?<=\.).*?(?= as)/", $column, $name);
        $name = trim($name[0]);

        return array($alias, $name);
    }
    

}
