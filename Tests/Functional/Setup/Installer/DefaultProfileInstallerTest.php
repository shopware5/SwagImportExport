<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Setup\Installer;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\Install\DefaultProfileInstaller;
use Shopware\Setup\SwagImportExport\SetupContext;

class DefaultProfileInstallerTest extends TestCase
{
    public function test_it_should_be_compatible()
    {
        $setupContext = new SetupContext('', '2.0.0', '');
        $dbalConnectionMock = $this->createMock(Connection::class);

        $installer = new DefaultProfileInstaller($setupContext, $dbalConnectionMock);
        $isCompatible = $installer->isCompatible();

        static::assertTrue($isCompatible);
    }

    public function test_it_should_be_incompatible()
    {
        $setupContext = new SetupContext('', '1.9.0', '');
        $dbalConnectionMock = $this->createMock(Connection::class);

        $installer = new DefaultProfileInstaller($setupContext, $dbalConnectionMock);
        $isCompatible = $installer->isCompatible();

        static::assertFalse($isCompatible);
    }
}
