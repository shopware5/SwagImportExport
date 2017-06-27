<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\SwagImportExport\Service\AutoImportServiceInterface;

/**
 * This is a controller and not a correct implementation of a Shopware cron job. By implementing the cron job as
 * a controller the execution of other cron jobs will not be triggered.
 */
class Shopware_Controllers_Backend_SwagImportExportCron extends Shopware_Controllers_Backend_ExtJs implements CSRFWhitelistAware
{
    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'cron',
        ];
    }

    public function init()
    {
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
    }

    /**
     * Check for terminal call on cron action
     */
    public function preDispatch()
    {
        //Call cron only if request is not from browser
        if (php_sapi_name() == 'cli') {
            $this->cronAction();
        }
    }

    /**
     * Custom cronjob for import
     */
    public function cronAction()
    {
        /** @var AutoImportServiceInterface $autoImporter */
        $autoImporter = $this->get('swag_import_export.auto_importer');
        $autoImporter->runAutoImport();
    }
}
