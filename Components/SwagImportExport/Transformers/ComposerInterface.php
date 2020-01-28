<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Transformers;

interface ComposerInterface
{
    /**
     * Composes the header of the formatted output file.
     */
    public function composeHeader();

    /**
     * Composes the footer of the formatted output file.
     */
    public function composeFooter();

    /**
     * Parses the header of the formatted input file data.
     */
    public function parseHeader(array $data);

    /**
     * Parses the footer of the formatted input file data.
     */
    public function parseFooter(array $data);
}
