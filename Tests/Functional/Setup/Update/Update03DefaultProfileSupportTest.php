<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Setup\Update;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\SetupContext;
use Shopware\Setup\SwagImportExport\Update\Update03DefaultProfileSupport;

class Update03DefaultProfileSupportTest extends TestCase
{
    public function test_it_should_be_compatible()
    {
        $updateFromVersion = '1.9.0';
        $setupContext = new SetupContext('', '', $updateFromVersion);
        $dbalConnectionMock = $this->createMock(Connection::class);
        $snippetManagerMock = $this->createMock(\Shopware_Components_Snippet_Manager::class);

        $updater = new Update03DefaultProfileSupport($setupContext, $dbalConnectionMock, $snippetManagerMock);
        $isCompatible = $updater->isCompatible();

        $this->assertTrue($isCompatible);
    }

    public function test_it_should_be_incompatible()
    {
        $updateFromVersion = '2.0.0';
        $setupContext = new SetupContext('', '', $updateFromVersion);
        $dbalConnectionMock = $this->createMock(Connection::class);
        $snippetManagerMock = $this->createMock(\Shopware_Components_Snippet_Manager::class);

        $updater = new Update03DefaultProfileSupport($setupContext, $dbalConnectionMock, $snippetManagerMock);
        $isCompatible = $updater->isCompatible();

        $this->assertFalse($isCompatible);
    }
}
