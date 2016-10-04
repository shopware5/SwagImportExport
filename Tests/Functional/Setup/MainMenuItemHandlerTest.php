<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Setup;

use Doctrine\ORM\EntityRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Menu\Menu;
use Shopware\Setup\SwagImportExport\MainMenuItemHandler;

class MainMenuItemHandlerTest extends \PHPUnit_Framework_TestCase
{
    const OLD_MENU_LABEL = 'Import/Export Advanced';
    const UPDATED_MENU_LABEL = 'Import/Export';
    const CREATED_MENU_LABEL = 'Import/Export';

    /**
     * @var MainMenuItemHandler
     */
    private $SUT;

    /**
     * @var ModelManager
     */
    private $modelManger;

    protected function setUp()
    {
        parent::setUp();
        $this->modelManger = Shopware()->Container()->get('models');
        $this->modelManger->beginTransaction();

        $this->SUT = new MainMenuItemHandler($this->modelManger);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->modelManger->rollback();
    }

    public function test_handle_menu_item_should_create_menu_item()
    {
        $menuRepository = $this->modelManger->getRepository(Menu::class);
        $this->removeActualMenuItem($menuRepository);

        $this->SUT->handleMenuItem();

        $newMenuItem = $menuRepository->findOneBy([ 'label' => self::CREATED_MENU_LABEL ]);
        $this->assertInstanceOf(Menu::class, $newMenuItem, 'Could not create menu item if no menu item was found.');
    }

    public function test_handle_menu_item_should_update_existing_item()
    {
        $expectedMenuItemController = 'SwagImportExport';
        $menuRepository = $this->modelManger->getRepository(Menu::class);

        $this->SUT->handleMenuItem();

        $updatedMenuItem = $menuRepository->findOneBy([ 'label' => self::UPDATED_MENU_LABEL ]);
        $this->assertEquals($expectedMenuItemController, $updatedMenuItem->getController(), 'Import/Export menu item could not be updated to use SwagImporExport controller.');
    }

    public function test_handle_menu_item_should_remove_old_menu_item()
    {
        $menuRepository = $this->modelManger->getRepository(Menu::class);

        $oldMenuItem = $menuRepository->findOneBy([ 'label' => self::OLD_MENU_LABEL ]);
        $this->createMenuItemIfItDoesNotExist($oldMenuItem, self::OLD_MENU_LABEL);

        $this->SUT->handleMenuItem();

        $removedMenuItem = $menuRepository->findOneBy([ 'label' => self::OLD_MENU_LABEL ]);
        $this->assertNull($removedMenuItem, 'Old menu item for SwagImportExport advanced should be removed on update or installation to 5.3.');
    }

    /**
     * @param EntityRepository $menuRepository
     */
    private function removeActualMenuItem(EntityRepository $menuRepository)
    {
        /** @var Menu $currentMenuItem */
        $currentMenuItem = $menuRepository->findOneBy(['label' => MainMenuItemHandler::CURRENT_MENU_LABEL]);
        if ($currentMenuItem instanceof Menu) {
            $this->modelManger->remove($currentMenuItem);
            $this->modelManger->flush();
        }
    }

    /**
     * @param Menu|null $menuItem
     * @param string $menuLabel
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