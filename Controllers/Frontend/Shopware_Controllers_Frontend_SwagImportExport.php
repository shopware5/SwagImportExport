<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Controllers\Frontend;

use Shopware\Components\Model\ModelManager;
use Shopware\Controllers\Backend\PluginManager;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Frontend_SwagImportExport extends \Enlight_Controller_Action
{
    protected ModelManager $manager;

    private PluginManager $pluginManager;

    public function __construct(
        PluginManager $pluginManager
    ) {
        $this->pluginManager = $pluginManager;
    }

    public function init(): void
    {
        $this->pluginManager->Backend()->Auth()->setNoAuth();
        $this->pluginManager->Controller()->ViewRenderer()->setNoRender();
    }

    /**
     * Check for terminal call for cron action
     */
    public function preDispatch(): void
    {
        // Call cron only if request is not from browser
        if (\PHP_SAPI === 'cli') {
            $this->cronAction();
        }
    }

    /**
     * Custom cronjob for import (forward request)
     */
    public function cronAction(): bool
    {
        return $this->forward('cron', 'SwagImportExportCron', 'backend');
    }
}
