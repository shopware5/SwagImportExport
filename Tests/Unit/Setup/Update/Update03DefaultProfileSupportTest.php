<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Unit\Setup\Update;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use PHPUnit\Framework\TestCase;
use Shopware\Setup\SwagImportExport\Exception\DuplicateNameException;
use Shopware\Setup\SwagImportExport\SetupContext;
use Shopware\Setup\SwagImportExport\Update\Update03DefaultProfileSupport;

class Update03DefaultProfileSupportTest extends TestCase
{
    public const DUPLICATE_NAME_ERROR_MESSAGE = 'Duplicate name entry exception';
    public const RANDOM_EXCEPTION_MESSAGE = 'Test exception message';
    public const DUPLICATE_NAME_EXCEPTION_MESSAGE = 'Duplicate entry ... something';
    public const ANY_VERSION = '0.0.0';

    public function testUpdateShouldThrowOriginalException()
    {
        $setProfileNameColumnUniqueUpdater = new Update03DefaultProfileSupport(
            new SetupContext(self::ANY_VERSION, self::ANY_VERSION, self::ANY_VERSION),
            $this->getDbalMockThrowsException(),
            $this->getSnippetManagerMock()
        );

        $this->expectException(DBALException::class);
        $this->expectExceptionMessage(self::RANDOM_EXCEPTION_MESSAGE);
        $setProfileNameColumnUniqueUpdater->update();
    }

    public function testUpdateShouldThrowDuplicateNameException()
    {
        $setProfileNameColumnUniqueUpdater = new Update03DefaultProfileSupport(
            new SetupContext(self::ANY_VERSION, self::ANY_VERSION, self::ANY_VERSION),
            $this->getDbalMockThrowsIntegrityConstraintException(),
            $this->getSnippetManagerMock()
        );

        $this->expectException(DuplicateNameException::class);
        $setProfileNameColumnUniqueUpdater->update();
    }

    private function getDbalMockThrowsException(): Connection
    {
        $dbalMock = $this->createMock(Connection::class);
        $dbalMock
            ->method('executeQuery')
            ->willThrowException(new DBALException(self::RANDOM_EXCEPTION_MESSAGE));

        return $dbalMock;
    }

    private function getDbalMockThrowsIntegrityConstraintException(): Connection
    {
        $dbalMock = $this->createMock(Connection::class);
        $dbalMock
            ->method('executeQuery')
            ->willThrowException(new DBALException(self::DUPLICATE_NAME_EXCEPTION_MESSAGE));

        return $dbalMock;
    }

    private function getSnippetManagerMock(): \Shopware_Components_Snippet_Manager
    {
        $snippetNamespaceMock = $this->createMock(\Enlight_Components_Snippet_Namespace::class);
        $snippetNamespaceMock->method('get')
            ->willReturn(self::DUPLICATE_NAME_ERROR_MESSAGE);

        $snippetManagerMock = $this->createMock(\Shopware_Components_Snippet_Manager::class);
        $snippetManagerMock->method('getNamespace')
            ->willReturn($snippetNamespaceMock);

        return $snippetManagerMock;
    }
}
