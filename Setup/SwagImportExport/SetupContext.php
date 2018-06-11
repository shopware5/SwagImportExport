<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport;

class SetupContext
{
    const NO_PREVIOUS_VERSION = '0';

    /**
     * @var string
     */
    private $shopwareVersion;

    /**
     * @var string
     */
    private $pluginVersion;

    /**
     * @var string
     */
    private $previousPluginVersion;

    /**
     * @param string $shopwareVersion
     * @param string $pluginVersion
     * @param string $previousPluginVersion
     */
    public function __construct($shopwareVersion, $pluginVersion, $previousPluginVersion)
    {
        $this->shopwareVersion = $shopwareVersion;
        $this->pluginVersion = $pluginVersion;
        $this->previousPluginVersion = $previousPluginVersion;
    }

    /**
     * @param string $minVersion
     *
     * @return bool
     */
    public function assertMinimumShopwareVersion($minVersion)
    {
        if ($this->shopwareVersion === '___VERSION___') {
            return true;
        }

        return version_compare($this->shopwareVersion, $minVersion, '>=');
    }

    /**
     * @param string $maxVersion
     *
     * @return bool
     */
    public function assertMaximumShopwareVersion($maxVersion)
    {
        if ($this->shopwareVersion === '___VERSION___') {
            return false;
        }

        return version_compare($this->shopwareVersion, $maxVersion, '<');
    }

    /**
     * @param string $minVersion
     *
     * @return bool
     */
    public function assertMinimumPluginVersion($minVersion)
    {
        return version_compare($this->pluginVersion, $minVersion, '>=');
    }

    /**
     * @param string $maxVersion
     *
     * @return bool
     */
    public function assertMaximumPluginVersion($maxVersion)
    {
        return version_compare($this->pluginVersion, $maxVersion, '<');
    }

    /**
     * @return string
     */
    public function getShopwareVersion()
    {
        return $this->shopwareVersion;
    }

    /**
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->pluginVersion;
    }

    /**
     * @return string
     */
    public function getPreviousPluginVersion()
    {
        return $this->previousPluginVersion;
    }
}
