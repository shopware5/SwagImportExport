<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Components\Model\ModelManager;

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Frontend_SwagImportExport extends Enlight_Controller_Action
{
    protected ModelManager $manager;

    public function init(): void
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
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
