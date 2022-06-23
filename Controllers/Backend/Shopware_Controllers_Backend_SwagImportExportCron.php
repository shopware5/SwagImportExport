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
use SwagImportExport\Components\Service\AutoImportServiceInterface;

/**
 * This is a controller and not a correct implementation of a Shopware cron job. By implementing the cron job as
 * a controller the execution of other cron jobs will not be triggered.
 */
class Shopware_Controllers_Backend_SwagImportExportCron extends \Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
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
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
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
        /** @var \Shopware_Plugins_Core_Cron_Bootstrap $cronBootstrap */
        $cronBootstrap = $this->getPluginBootstrap('Cron');
        if ($cronBootstrap && !$cronBootstrap->authorizeCronAction($this->Request())) {
            $this->Response()
                ->clearHeaders()
                ->setHttpResponseCode(403)
                ->appendBody('Forbidden');

            return;
        }

        /** @var AutoImportServiceInterface $autoImporter */
        $autoImporter = $this->get('swag_import_export.auto_importer');
        $autoImporter->runAutoImport();
    }

    /**
     * Returns plugin bootstrap if plugin exits, is enabled, and active.
     * Otherwise return null.
     */
    private function getPluginBootstrap(string $pluginName): ?\Enlight_Plugin_Bootstrap
    {
        /** @var \Shopware_Components_Plugin_Namespace $namespace */
        $namespace = $this->get('plugin_manager')->Core();
        $pluginBootstrap = $namespace->get($pluginName);

        if (!$pluginBootstrap instanceof \Enlight_Plugin_Bootstrap) {
            return null;
        }

        /** @var Plugin $plugin */
        $plugin = $this->get('models')->find(Plugin::class, $pluginBootstrap->getId());
        if (!$plugin) {
            return null;
        }

        if (!$plugin->getActive() || !$plugin->getInstalled()) {
            return null;
        }

        return $pluginBootstrap;
    }
}
