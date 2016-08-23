<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
