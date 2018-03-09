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
    const DUPLICATE_NAME_ERROR_MESSAGE = 'Duplicate name entry exception';
    const RANDOM_EXCEPTION_MESSAGE = 'Test exception message';
    const DUPLICATE_NAME_EXCEPTION_MESSAGE = 'Duplicate entry ... something';
    const ANY_VERSION = '0.0.0';

    public function test_update_should_throw_original_exception()
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

    public function test_update_should_throw_DuplicateNameException()
    {
        $setProfileNameColumnUniqueUpdater = new Update03DefaultProfileSupport(
            new SetupContext(self::ANY_VERSION, self::ANY_VERSION, self::ANY_VERSION),
            $this->getDbalMockThrowsIntegrityConstraintException(),
            $this->getSnippetManagerMock()
        );

        $this->expectException(DuplicateNameException::class);
        $setProfileNameColumnUniqueUpdater->update();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function getDbalMockThrowsException()
    {
        $dbalMock = $this->createMock(Connection::class);
        $dbalMock
            ->method('executeQuery')
            ->willThrowException(new DBALException(self::RANDOM_EXCEPTION_MESSAGE));

        return $dbalMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function getDbalMockThrowsIntegrityConstraintException()
    {
        $dbalMock = $this->createMock(Connection::class);
        $dbalMock
            ->method('executeQuery')
            ->willThrowException(new DBALException(self::DUPLICATE_NAME_EXCEPTION_MESSAGE));

        return $dbalMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Shopware_Components_Snippet_Manager
     */
    private function getSnippetManagerMock()
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
