<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Backend;

use Shopware\Components\CSRFWhitelistAware;
use Shopware\Models\Plugin\Plugin;

/**
 * This is a controller and not a correct implementation of a Shopware cron job. By implementing the cron job as
 * a controller the execution of other cron jobs will not be triggered.
 */
class Shopware_Controllers_Backend_SwagImportExportCron extends \Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    private \Enlight_Plugin_PluginManager $pluginManager;

    public function __construct(\Enlight_Plugin_PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions(): array
    {
        return [
            'cron',
        ];
    }

    public function init(): void
    {
        $this->pluginManager->Backend()->Auth()->setNoAuth();
        $this->pluginManager->Controller()->ViewRenderer()->setNoRender();
    }

    /**
     * Check for terminal call on cron action
     */
    public function preDispatch(): void
    {
        // Call cron only if request is not from browser
        if (\PHP_SAPI === 'cli') {
            $this->cronAction();
        }
    }

    /**
     * Custom cronjob for import
     */
    public function cronAction(): void
    {
        $cronBootstrap = $this->getCronPluginBootstrap();
        if ($cronBootstrap && !$cronBootstrap->authorizeCronAction($this->Request())) {
            $this->Response()
                ->clearHeaders()
                ->setHttpResponseCode(403)
                ->appendBody('Forbidden');

            return;
        }

        $autoImporter = $this->get('swag_import_export.auto_importer');
        $autoImporter->runAutoImport();
    }

    /**
     * Returns plugin bootstrap if plugin exits, is enabled, and active.
     * Otherwise, return null.
     */
    private function getCronPluginBootstrap(): ?\Shopware_Plugins_Core_Cron_Bootstrap
    {
        $pluginBootstrap = $this->get('plugin_manager')->Core()->get('Cron');

        if (!$pluginBootstrap instanceof \Shopware_Plugins_Core_Cron_Bootstrap) {
            return null;
        }

        $plugin = $this->getModelManager()->find(Plugin::class, $pluginBootstrap->getId());

        if (!$plugin instanceof Plugin) {
            return null;
        }

        if (!$plugin->getActive() || !$plugin->getInstalled()) {
            return null;
        }

        return $pluginBootstrap;
    }
}
