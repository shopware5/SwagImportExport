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
use PHPUnit\Framework\TestCase;
use Shopware\Components\Model\ModelManager;
use Shopware\Setup\SwagImportExport\SetupContext;
use Shopware\Setup\SwagImportExport\Update\Update02RemoveForeignKeyConstraint;

class Update02RemoveForeignKeyConstraintTest extends TestCase
{
    public function testItShouldBeCompatible()
    {
        $setupContext = new SetupContext('', '', '1.2.0');
        $dbalConnectionMock = $this->createMock(Connection::class);
        $modelManagerMock = $this->createMock(ModelManager::class);
        $abstractSchemaMock = $this->createMock(AbstractSchemaManager::class);

        $update = new Update02RemoveForeignKeyConstraint($setupContext, $dbalConnectionMock, $modelManagerMock, $abstractSchemaMock);
        $isCompatible = $update->isCompatible();

        static::assertTrue($isCompatible);
    }

    public function testItShouldBeIncompatible()
    {
        $setupContext = new SetupContext('', '', '1.3.0');
        $dbalConnectionMock = $this->createMock(Connection::class);
        $modelManagerMock = $this->createMock(ModelManager::class);
        $abstractSchemaMock = $this->createMock(AbstractSchemaManager::class);

        $update = new Update02RemoveForeignKeyConstraint($setupContext, $dbalConnectionMock, $modelManagerMock, $abstractSchemaMock);
        $isCompatible = $update->isCompatible();

        static::assertFalse($isCompatible);
    }

    public function testItShouldBeIncompatibleWithVersionHigherOrEquals200()
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

        static::assertFalse($isCompatible);
    }
}
