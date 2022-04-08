<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Subscribers;

use Enlight\Event\SubscriberInterface;

class ControllerSubscriber implements SubscriberInterface
{
    private string $pluginDirectory;

    public function __construct(
        string $pluginDirectory
    ) {
        $this->pluginDirectory = $pluginDirectory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Index' => 'injectBackendAceEditor',
        ];
    }

    /**
     * Injects Ace Editor used in Conversions GUI
     */
    public function injectBackendAceEditor(\Enlight_Event_EventArgs $args): void
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
