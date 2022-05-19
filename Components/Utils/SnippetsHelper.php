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
    /**
     * @param string $namespace
     *
     * @return \Enlight_Components_Snippet_Namespace
     */
    public static function getNamespace($namespace = 'backend/swag_importexport/main')
    {
        return Shopware()->Snippets()->getNamespace($namespace);
    }
}
