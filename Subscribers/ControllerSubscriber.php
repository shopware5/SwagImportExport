<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Subscribers;

use Enlight\Event\SubscriberInterface;

class ControllerSubscriber implements SubscriberInterface
{
    private string $pluginDirectory;

    private \Enlight_Template_Manager $template;

    public function __construct(
        string $pluginDirectory,
        \Enlight_Template_Manager $template
    ) {
        $this->pluginDirectory = $pluginDirectory;
        $this->template = $template;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'injectBackendAceEditor',
            'Enlight_Controller_Action_PreDispatch' => 'loadViews',
        ];
    }

    public function loadViews(): void
    {
        $this->template->addTemplateDir($this->pluginDirectory . '/Resources/views/');
    }

    /**
     * Injects Ace Editor used in Conversions GUI
     */
    public function injectBackendAceEditor(\Enlight_Event_EventArgs $args): void
    {
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
