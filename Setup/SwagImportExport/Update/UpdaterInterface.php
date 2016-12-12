<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Update;

interface UpdaterInterface
{
    /**
     * Implements the logic which will be executed at the update process.
     */
    public function update();

    /**
     * Checks the compatibility of the update to a plugin version and the current shopware version.
     *
     * @return bool
     */
    public function isCompatible();
}
