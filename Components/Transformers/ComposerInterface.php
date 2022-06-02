<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

interface ComposerInterface
{
    /**
     * Composes the header of the formatted output file.
     */
    public function composeHeader(): array;

    /**
     * Composes the footer of the formatted output file.
     */
    public function composeFooter(): array;
}
