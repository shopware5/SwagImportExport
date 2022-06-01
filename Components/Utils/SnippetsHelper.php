<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Utils;

class SnippetsHelper
{
    public static function getNamespace(string $namespace = 'backend/swag_importexport/main'): \Enlight_Components_Snippet_Namespace
    {
        return Shopware()->Snippets()->getNamespace($namespace);
    }
}
