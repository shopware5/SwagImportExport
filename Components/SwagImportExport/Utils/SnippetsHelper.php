<?php

namespace Shopware\Components\SwagImportExport\Utils;

class SnippetsHelper
{
    /**
     * @return \Enlight_Components_Snippet_Namespace
     */
    public static function getNamespace()
    {
        return Shopware()->Snippets()->getNamespace('backend/swag_importexport/main');
    }
}
