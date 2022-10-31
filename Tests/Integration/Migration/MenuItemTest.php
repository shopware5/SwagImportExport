<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\Migration\Mock\BackendIndexControllerMock;

class MenuItemTest extends TestCase
{
    use ContainerTrait;

    public function testMenuItemIsAvailable(): void
    {
        $backendIndexController = new BackendIndexControllerMock();
        $view = new \Enlight_View_Default(new \Enlight_Template_Manager());
        $backendIndexController->setView($view);
        $backendIndexController->setContainer($this->getContainer());
        $auth = $this->createMock(\Shopware_Plugins_Backend_Auth_Bootstrap::class);
        $auth->expects(static::once())->method('checkAuth')->willReturn(true);
        $backendIndexController->setAuth($auth);
        $backendIndexController->menuAction();

        $pluginId = (int) $this->getContainer()->get(Connection::class)->fetchOne("SELECT id FROM s_core_plugins WHERE name = 'SwagImportExport'");
        foreach ($view->getAssign('menu') as $firstLevelMenuItem) {
            if ($firstLevelMenuItem['controller'] !== 'Content') {
                continue;
            }
            foreach ($firstLevelMenuItem['children'] as $secondLevelMenuItem) {
                if ($secondLevelMenuItem['controller'] === 'SwagImportExport') {
                    static::assertSame($pluginId, $secondLevelMenuItem['pluginId']);

                    return;
                }
            }
        }

        static::fail('ImportExport menu item not found');
    }
}
