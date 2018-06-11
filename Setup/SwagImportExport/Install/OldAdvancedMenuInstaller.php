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
 * Adds the old advanced import/export menu item.
 */
class OldAdvancedMenuInstaller implements InstallerInterface
{
    const SHOPWARE_MAX_VERSION = '5.3.0';

    const MENU_LABEL = 'Import/Export Advanced';
    const PLUGIN_NAME = 'SwagImportExport';
    const MENU_ITEM_CLASS = 'sprite-server--plus';

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
     * {@inheritdoc}
     */
    public function install()
    {
        $parentMenuItem = $this->findParent();
        $plugin = $this->findPlugin();

        $menuItem = new Menu();
        $menuItem->setLabel(self::MENU_LABEL);
        $menuItem->setController(self::PLUGIN_NAME);
        $menuItem->setAction('Index');
        $menuItem->setClass(self::MENU_ITEM_CLASS);
        $menuItem->setActive(1);
        $menuItem->setParent($parentMenuItem);
        $menuItem->setPlugin($plugin);
        $menuItem->setPosition(6);

        $this->modelManager->persist($menuItem);
        $this->modelManager->flush();
    }

    /**
     * @return bool
     */
    public function isCompatible()
    {
        return $this->setupContext->assertMaximumShopwareVersion(self::SHOPWARE_MAX_VERSION);
    }

    /**
     * @return null|Menu
     */
    private function findParent()
    {
        $menuRepository = $this->modelManager->getRepository(Menu::class);

        return $menuRepository->findOneBy(['label' => 'Inhalte']);
    }

    /**
     * @return null|Plugin
     */
    private function findPlugin()
    {
        $pluginRepository = $this->modelManager->getRepository(Plugin::class);

        return $pluginRepository->findOneBy(['name' => 'SwagImportExport']);
    }
}
