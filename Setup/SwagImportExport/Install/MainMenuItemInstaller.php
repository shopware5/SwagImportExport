<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Setup\SwagImportExport\Install;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Menu\Menu;
use Shopware\Models\Plugin\Plugin;
use Shopware\Setup\SwagImportExport\SetupContext;

/**
 * Adds or replaces the new menu item with the old import/export menu item.
 */
class MainMenuItemInstaller implements InstallerInterface
{
    const SHOPWARE_MIN_VERSION = '5.3.0';

    const SWAG_IMPORT_EXPORT_CONTROLLER = 'SwagImportExport';
    const SWAG_IMPORT_EXPORT_ACTION = 'index';
    const OLD_MENU_LABEL = 'Import/Export Advanced';
    const CURRENT_MENU_LABEL = 'Import/Export';
    const CURRENT_MENU_ITEM_CLASS = 'sprite-arrow-circle-double-135 contents--import-export';
    const PARENT_MENU_LABEL_FOR_CURRENT_MENU = 'Inhalte';

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var SetupContext
     */
    private $setupContext;

    /**
     * @param SetupContext $setupContext
     * @param ModelManager $modelManager
     */
    public function __construct(SetupContext $setupContext, ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->setupContext = $setupContext;
    }

    /**
     * Updates the teaser menu, creates a new menu item and removes the old menu item.
     */
    public function install()
    {
        $this->removeImportExportAdvancedMenuItem();

        $currentMenuItem = $this->findMenuItemByLabel(self::CURRENT_MENU_LABEL);

        $pluginRepository = $this->modelManager->getRepository(Plugin::class);
        $plugin = $pluginRepository->findOneBy(['name' => 'SwagImportExport']);

        if ($this->menuItemExists($currentMenuItem)) {
            $this->updateImportExportMenuItem($currentMenuItem, $plugin);

            return;
        }

        $this->createMenuItem($plugin);
    }

    /**
     * @return bool
     */
    public function isCompatible()
    {
        return $this->setupContext->assertMinimumShopwareVersion(self::SHOPWARE_MIN_VERSION);
    }

    /**
     * @param Plugin $plugin
     */
    private function createMenuItem(Plugin $plugin)
    {
        $parentMenuItem = $this->findMenuItemByLabel(self::PARENT_MENU_LABEL_FOR_CURRENT_MENU);

        $menu = new Menu();
        $menu->setPlugin($plugin);
        $menu->setLabel(self::CURRENT_MENU_LABEL);
        $menu->setController(self::SWAG_IMPORT_EXPORT_CONTROLLER);
        $menu->setAction(self::SWAG_IMPORT_EXPORT_ACTION);
        $menu->setClass(self::CURRENT_MENU_ITEM_CLASS);
        $menu->setParent($parentMenuItem);
        $menu->setActive(true);
        $menu->setPosition(3);

        $this->modelManager->persist($menu);
        $this->modelManager->flush();
    }

    /**
     * Remove unnecessary menu item of Import/Export Advanced
     */
    private function removeImportExportAdvancedMenuItem()
    {
        $oldMenuItem = $this->findMenuItemByLabel(self::OLD_MENU_LABEL);
        if ($this->menuItemExists($oldMenuItem)) {
            $this->modelManager->remove($oldMenuItem);
            $this->modelManager->flush();
        }
    }

    /**
     * @param Menu   $menuItem
     * @param Plugin $plugin
     */
    private function updateImportExportMenuItem(Menu $menuItem, Plugin $plugin)
    {
        $menuItem->setController(self::SWAG_IMPORT_EXPORT_CONTROLLER);
        $menuItem->setAction(self::SWAG_IMPORT_EXPORT_ACTION);
        $menuItem->setPlugin($plugin);

        $this->modelManager->persist($menuItem);
        $this->modelManager->flush();
    }

    /**
     * @param mixed $menuItem
     *
     * @return bool
     */
    private function menuItemExists($menuItem)
    {
        return $menuItem instanceof Menu;
    }

    /**
     * @param string $label
     *
     * @return Menu|null
     */
    private function findMenuItemByLabel($label)
    {
        $menuRepository = $this->modelManager->getRepository(Menu::class);

        return $menuRepository->findOneBy(['label' => $label]);
    }
}
