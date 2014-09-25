<?php

namespace Shopware\Components\SwagImportExport\Utils;

class SnippetsHelper
{
    public static function getNamespace()
    {
        return Shopware()->Snippets()->getNamespace('backend/swag_importexport/main');
    }
}
