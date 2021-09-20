<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Setup;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Menu\Menu;
use Shopware\Setup\SwagImportExport\Install\MainMenuItemInstaller;
use Shopware\Setup\SwagImportExport\SetupContext;

class MainMenuItemInstallerTest extends TestCase
{
    public const OLD_MENU_LABEL = 'Import/Export Advanced';
    public const UPDATED_MENU_LABEL = 'Import/Export';
    public const CREATED_MENU_LABEL = 'Import/Export';
    public const RANDOM_PLUGIN_VERSION = '0';

    /**
     * @var MainMenuItemInstaller
     */
    private $mainMenuItemInstaller;

    /**
     * @var ModelManager
     */
    private $modelManger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelManger = Shopware()->Container()->get('models');
        $this->modelManger->beginTransaction();

        $setupContext = new SetupContext(
            MainMenuItemInstaller::SHOPWARE_MIN_VERSION,
            self::RANDOM_PLUGIN_VERSION,
            self::RANDOM_PLUGIN_VERSION
        );
        $this->mainMenuItemInstaller = new MainMenuItemInstaller($setupContext, $this->modelManger);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->modelManger->rollback();
    }

    public function testInstallShouldCreateMenuItem()
    {
        $menuRepository = $this->modelManger->getRepository(Menu::class);
        $this->removeActualMenuItem($menuRepository);

        $this->mainMenuItemInstaller->install();

        $newMenuItem = $menuRepository->findOneBy(['label' => self::CREATED_MENU_LABEL]);
        static::assertInstanceOf(Menu::class, $newMenuItem, 'Could not create menu item if no menu item was found.');
    }

    public function testInstallShouldUpdateExistingItem()
    {
        $expectedMenuItemController = 'SwagImportExport';
        $menuRepository = $this->modelManger->getRepository(Menu::class);

        $this->mainMenuItemInstaller->install();

        $updatedMenuItem = $menuRepository->findOneBy(['label' => self::UPDATED_MENU_LABEL]);
        static::assertEquals($expectedMenuItemController, $updatedMenuItem->getController(), 'Import/Export menu item could not be updated to use SwagImporExport controller.');
    }

    public function testInstallShouldRemoveOldMenuItem()
    {
        $menuRepository = $this->modelManger->getRepository(Menu::class);

        $oldMenuItem = $menuRepository->findOneBy(['label' => self::OLD_MENU_LABEL]);
        $this->createMenuItemIfItDoesNotExist($oldMenuItem, self::OLD_MENU_LABEL);

        $this->mainMenuItemInstaller->install();

        $removedMenuItem = $menuRepository->findOneBy(['label' => self::OLD_MENU_LABEL]);
        static::assertNull($removedMenuItem, 'Old menu item for SwagImportExport advanced should be removed on update or installation to 5.3.');
    }

    public function testItShouldBeCompatible()
    {
        $setupContext = new SetupContext('5.3.0', '', '');
        $modelManagerMock = $this->createMock(ModelManager::class);

        $mainMenuItemInstaller = new MainMenuItemInstaller($setupContext, $modelManagerMock);
        $isCompatible = $mainMenuItemInstaller->isCompatible();

        static::assertTrue($isCompatible);
    }

    public function testItShouldBeCompatibleWithGreaterVersion()
    {
        $setupContext = new SetupContext('5.3.5', '', '');
        $modelManagerMock = $this->createMock(ModelManager::class);

        $mainMenuItemInstaller = new MainMenuItemInstaller($setupContext, $modelManagerMock);
        $isCompatible = $mainMenuItemInstaller->isCompatible();

        static::assertTrue($isCompatible);
    }

    public function testItShouldBeIncompatible()
    {
        $setupContext = new SetupContext('5.2.0', '', '');
        $modelManagerMock = $this->createMock(ModelManager::class);

        $mainMenuItemInstaller = new MainMenuItemInstaller($setupContext, $modelManagerMock);
        $isCompatible = $mainMenuItemInstaller->isCompatible();

        static::assertFalse($isCompatible);
    }

    private function removeActualMenuItem(EntityRepository $menuRepository)
    {
        /** @var Menu $currentMenuItem */
        $currentMenuItem = $menuRepository->findOneBy(['label' => MainMenuItemInstaller::CURRENT_MENU_LABEL]);
        if ($currentMenuItem instanceof Menu) {
            $this->modelManger->remove($currentMenuItem);
            $this->modelManger->flush();
        }
    }

    /**
     * @param Menu|null $menuItem
     * @param string    $menuLabel
     */
    private function createMenuItemIfItDoesNotExist($menuItem, $menuLabel)
    {
        if ($menuItem instanceof Menu) {
            return;
        }

        $menu = new Menu();
        $menu->setLabel($menuLabel);
        $this->modelManger->persist($menu);
        $this->modelManger->flush();
    }
}
