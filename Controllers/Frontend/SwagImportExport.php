<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Shopware ImportExport Plugin
 */
class Shopware_Controllers_Frontend_SwagImportExport extends Enlight_Controller_Action
{
    /**
     * Shopware\Components\Model\ModelManager
     */
    protected $manager;

    public function init()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
    }

    /**
     * Check for terminal call for cron action
     */
    public function preDispatch()
    {
        // Call cron only if request is not from browser
        if (\PHP_SAPI === 'cli') {
            $this->cronAction();
        }
    }

    /**
     * Custom cronjob for import (forward request)
     *
     * @return bool
     */
    public function cronAction()
    {
        return $this->forward('cron', 'SwagImportExportCron', 'backend');
    }
}
