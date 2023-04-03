<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportImport;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportSession;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\TestViewMock;
use Symfony\Component\HttpFoundation\Request;

class SessionTest extends TestCase
{
    use ExportControllerTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    private const PRODUCT_IMPORT_FILE = 'ArticleImport.xml';
    private const PRODUCT_IMPORT_PROFILE_ID = 5;

    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->getContainer()->get('dbal_connection');
    }

    public function testDeleteSessionActionShallDeleteAssociatedDatabaseEntriesInLogTable(): void
    {
        $sqlSession = 'SELECT * FROM s_import_export_session';
        $sqlLog = 'SELECT * FROM s_import_export_log';
        static::assertSame(0, $this->connection->executeQuery($sqlSession)->rowCount());
        static::assertSame(0, $this->connection->executeQuery($sqlLog)->rowCount());

        $this->doAnImport();
        static::assertSame(1, $this->connection->executeQuery($sqlSession)->rowCount());
        static::assertSame(1, $this->connection->executeQuery($sqlLog)->rowCount());

        $this->deleteSession();
        static::assertSame(0, $this->connection->executeQuery($sqlSession)->rowCount());
        static::assertSame(0, $this->connection->executeQuery($sqlLog)->rowCount());
    }

    private function doAnImport(): void
    {
        $importController = $this->createImportController();
        $view = new TestViewMock();
        $importController->setView($view);

        copy(\ImportExportTestKernel::IMPORT_FILES_DIR . self::PRODUCT_IMPORT_FILE, $this->getUploadFileProvider()->getPath() . '/' . self::PRODUCT_IMPORT_FILE);

        $importController->importAction(new Request([
            'profileId' => self::PRODUCT_IMPORT_PROFILE_ID,
            'importFile' => self::PRODUCT_IMPORT_FILE,
        ]));

        static::assertTrue($view->getAssign('success'));
    }

    private function deleteSession(): void
    {
        $sessionController = $this->createSessionController();
        $view = new TestViewMock();
        $sessionController->setView($view);

        $sessionId = $this->connection->executeQuery('SELECT id FROM s_import_export_session')->fetchOne();

        $params = [
            'data' => [
                'id' => $sessionId,
            ],
        ];
        $sessionController->setRequest(new \Enlight_Controller_Request_RequestTestCase($params));
        $sessionController->deleteSessionAction();

        static::assertTrue($view->getAssign('success'));
    }

    private function createImportController(): Shopware_Controllers_Backend_SwagImportExportImport
    {
        $controller = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportImport::class);
        $controller->setContainer($this->getContainer());

        return $controller;
    }

    private function createSessionController(): Shopware_Controllers_Backend_SwagImportExportSession
    {
        $controller = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportSession::class);
        $controller->setContainer($this->getContainer());

        return $controller;
    }

    private function getUploadFileProvider(): UploadPathProvider
    {
        return $this->getContainer()->get(UploadPathProvider::class);
    }
}
