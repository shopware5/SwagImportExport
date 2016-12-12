<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Setup\Update;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Shopware\Components\Model\ModelManager;
use Shopware\Setup\SwagImportExport\SetupContext;
use Shopware\Setup\SwagImportExport\Update\Update02RemoveForeignKeyConstraint;

class Update02RemoveForeignKeyConstraintTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_should_be_compatible()
    {
        $setupContext = new SetupContext('', '', '1.2.0');
        $dbalConnectionMock = $this->createMock(Connection::class);
        $modelManagerMock = $this->createMock(ModelManager::class);
        $abstractSchemaMock = $this->createMock(AbstractSchemaManager::class);

        $update = new Update02RemoveForeignKeyConstraint($setupContext, $dbalConnectionMock, $modelManagerMock, $abstractSchemaMock);
        $isCompatible = $update->isCompatible();

        $this->assertTrue($isCompatible);
    }

    public function test_it_should_be_incompatible()
    {
        $setupContext = new SetupContext('', '', '1.3.0');
        $dbalConnectionMock = $this->createMock(Connection::class);
        $modelManagerMock = $this->createMock(ModelManager::class);
        $abstractSchemaMock = $this->createMock(AbstractSchemaManager::class);

        $update = new Update02RemoveForeignKeyConstraint($setupContext, $dbalConnectionMock, $modelManagerMock, $abstractSchemaMock);
        $isCompatible = $update->isCompatible();

        $this->assertFalse($isCompatible);
    }

    public function test_it_should_be_incompatible_with_version_higher_or_equals_200()
    {
        $updateFromVersion = '1.2.0';
        $updateToVersion = '2.0.0';
        $setupContext = new SetupContext('', $updateToVersion, $updateFromVersion);

        $updater = new Update02RemoveForeignKeyConstraint(
            $setupContext,
            $this->createMock(Connection::class),
            $this->createMock(ModelManager::class),
            $this->createMock(AbstractSchemaManager::class)
        );

        $isCompatible = $updater->isCompatible();

        $this->assertFalse($isCompatible);
    }
}
