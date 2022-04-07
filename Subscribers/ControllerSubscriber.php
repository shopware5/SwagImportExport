<?php
declare(strict_types=1);

namespace Shopware\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\SwagImportExport\FileIO\CsvFileWriter;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Service\AutoImportService;
use Shopware\Components\SwagImportExport\Service\AutoImportServiceInterface;
use Shopware\Components\SwagImportExport\Service\ExportService;
use Shopware\Components\SwagImportExport\Service\ImportService;
use Shopware\Components\SwagImportExport\Service\ProfileService;
use Shopware\Components\SwagImportExport\UploadPathProvider;
use Shopware\Components\SwagImportExport\Utils\FileHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ControllerSubscriber implements SubscriberInterface
{
    private ContainerInterface $container;

    private string $pluginDirectory;

    public function __construct(
        string $pluginDirectory,
        ContainerInterface $container
    )
    {
        $this->pluginDirectory = $pluginDirectory;
        $this->container = $container;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'injectBackendAceEditor'
        ];
    }

    /**
     * Injects Ace Editor used in Conversions GUI
     */
    public function injectBackendAceEditor(\Enlight_Event_EventArgs $args)
    {
        /** @var \Shopware_Controllers_Backend_Index $controller */
        $controller = $args->get('subject');
        $request = $controller->Request();
        $response = $controller->Response();
        $view = $controller->View();

        if (!$request->isDispatched() || $response->isException() || !$view->hasTemplate()) {
            return;
        }

        $view->addTemplateDir($this->pluginDirectory . '/Resources/views/');
        $view->extendsTemplate('backend/swag_import_export/menu_entry.tpl');
    }
}
