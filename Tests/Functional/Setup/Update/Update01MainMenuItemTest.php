<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Setup\Update;

use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Setup\SwagImportExport\SetupContext;
use Shopware\Setup\SwagImportExport\Update\Update01MainMenuItem;

class Update01MainMenuItemTest extends TestCase
{
    public function test_it_should_be_compatible()
    {
        $setupContext = new SetupContext('5.3.0', '', '');
        $modelManagerMock = $this->createMock(ModelManager::class);

        $updater = new Update01MainMenuItem($setupContext, $modelManagerMock);
        $isCompatible = $updater->isCompatible();

        static::assertTrue($isCompatible);
    }

    public function test_it_should_be_incompatible()
    {
        $setupContext = new SetupContext('5.2.0', '', '');
        $modelManagerMock = $this->createMock(ModelManager::class);

        $updater = new Update01MainMenuItem($setupContext, $modelManagerMock);
        $isCompatible = $updater->isCompatible();

        static::assertFalse($isCompatible);
    }
}
