<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Subscribers\ControllerRegistration;

use Enlight\Event\SubscriberInterface;
use Enlight_Template_Manager;

class BackendControllerSubscriber implements SubscriberInterface
{
    private Enlight_Template_Manager $template;

    private string $pluginDirectory;

    public function __construct(string $pluginDirectory, Enlight_Template_Manager $template)
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExport' => 'onSwagImportExport',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportConversion' => 'onSwagImportExportConversion',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportCron' => 'onSwagImportExportCron',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportExport' => 'onSwagImportExportExport',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportImport' => 'onSwagImportExportImport',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportProfile' => 'onSwagImportExportProfile',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_SwagImportExportSession' => 'onSwagImportExportSession',
        ];
    }

    public function onSwagImportExport(): string
    {
        $this->template->addTemplateDir($this->pluginDirectory . '/Resources/views/');

        return $this->pluginDirectory . '/Controllers/Backend/SwagImportExport.php';
    }

    public function onSwagImportExportConversion(): string
    {
        $this->template->addTemplateDir($this->pluginDirectory . '/Resources/views/');

        return $this->pluginDirectory . '/Controllers/Backend/SwagImportExportConversion.php';
    }

    public function onSwagImportExportExport(): string
    {
        $this->template->addTemplateDir($this->pluginDirectory . '/Resources/views/');

        return $this->pluginDirectory . '/Controllers/Backend/SwagImportExportExport.php';
    }

    public function onSwagImportExportImport(): string
    {
        $this->template->addTemplateDir($this->pluginDirectory . '/Resources/views/');

        return $this->pluginDirectory . '/Controllers/Backend/SwagImportExportImport.php';
    }

    public function onSwagImportExportProfile(): string
    {
        return $this->pluginDirectory . '/Controllers/Backend/SwagImportExportProfile.php';
    }

    public function onSwagImportExportSession(): string
    {
        $this->template->addTemplateDir($this->pluginDirectory . '/Resources/views/');

        return $this->pluginDirectory . '/Controllers/Backend/SwagImportExportSession.php';
    }
}
