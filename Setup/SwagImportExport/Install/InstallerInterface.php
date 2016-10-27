<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Install;

interface InstallerInterface
{
    /**
     * Implements the logic which will be executed on the plugin installation.
     */
    public function install();

    /**
     * Checks the compatibility of the update to a plugin version and the current shopware version.
     *
     * @return bool
     */
    public function isCompatible();
}
