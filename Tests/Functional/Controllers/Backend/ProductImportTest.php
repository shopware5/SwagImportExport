<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportImport;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\TestViewMock;
use Symfony\Component\HttpFoundation\Request;

class ProductImportTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    private const DEFAULT_PRODUCT_PROFILE_ID = 5;
    private const DEFAULT_PRODUCT_COMPLETE_PROFILE_ID = 4;

    public function testPrepareImportProductImportFile(): void
    {
        $importController = $this->getImportController();
        $view = new TestViewMock();

        $importController->setView($view);

        copy(\ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.xml', $this->getUploadFileProvider()->getPath() . '/ArticleImport.xml');

        $importController->prepareImportAction(new Request([
            'profileId' => self::DEFAULT_PRODUCT_PROFILE_ID,
            'importFile' => 'ArticleImport.xml',
        ]));

        static::assertTrue($view->getAssign('success'));
        static::assertSame(2, $view->getAssign('count'));
    }

    public function testPrepareImportProductImportFileNoProfile(): void
    {
        $importController = $this->getImportController();
        $view = new TestViewMock();
        $importController->setView($view);

        $importController->prepareImportAction(new Request());
        static::assertFalse($view->getAssign('success'));
        static::assertSame('Request parameter "profileId" must be set', $view->getAssign('msg'));
    }

    public function testPrepareImportProductImportFileNoFile(): void
    {
        $importController = $this->getImportController();
        $view = new TestViewMock();
        $importController->setView($view);

        $importController->prepareImportAction(new Request([
            'profileId' => self::DEFAULT_PRODUCT_PROFILE_ID,
        ]));
        static::assertFalse($view->getAssign('success'));
        static::assertSame('Request parameter "importFile" must be set', $view->getAssign('msg'));
    }

    public function testPrepareImportProductImportFileWithWrongFileFormat(): void
    {
        $importController = $this->getImportController();
        $view = new TestViewMock();
        $importController->setView($view);

        $importController->prepareImportAction(new Request([
            'profileId' => self::DEFAULT_PRODUCT_PROFILE_ID,
            'importFile' => 'InvalidFileFormat.txt',
        ]));

        static::assertFalse($view->getAssign('success'));
        static::assertSame('File reader txt does not exist.', $view->getAssign('msg'));
    }

    public function testImportProductImportFileWithBatchSizeOne(): void
    {
        $this->setImportBatchSize();

        $importController = $this->getImportController();
        $view = new TestViewMock();

        $importController->setView($view);

        copy(\ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.xml', $this->getUploadFileProvider()->getPath() . '/ArticleImport.xml');

        $importController->importAction(new Request([
            'profileId' => self::DEFAULT_PRODUCT_PROFILE_ID,
            'importFile' => 'ArticleImport.xml',
        ]));

        static::assertSame(1, $view->getAssign('data')['position']);
    }

    public function testImportProductImportFileWithImage(): void
    {
        $importController = $this->getImportController();
        $view = new TestViewMock();
        $importController->setView($view);

        $csvFile = \ImportExportTestKernel::IMPORT_FILES_DIR . 'ProductWIthImageImport.csv';
        $fixtureImagePath = 'file://' . \ImportExportTestKernel::IMPORT_FILES_DIR . 'sw-icon_blue128.png';
        $csvContentWithExternalImagePath = \str_replace('[placeholder_for_fixture_image]', $fixtureImagePath, (string) \file_get_contents($csvFile));
        \file_put_contents($csvFile, $csvContentWithExternalImagePath);

        copy(\ImportExportTestKernel::IMPORT_FILES_DIR . 'ProductWIthImageImport.csv', $this->getUploadFileProvider()->getPath() . '/ProductWIthImageImport.csv');

        $importController->importAction(new Request([
            'profileId' => self::DEFAULT_PRODUCT_COMPLETE_PROFILE_ID,
            'importFile' => 'ProductWIthImageImport.csv',
        ]));

        static::assertTrue($view->getAssign('success'));
        $data = $view->getAssign('data');
        static::assertIsArray($data);
        static::assertSame('ProductWIthImageImport.csv-articlesImages-swag.csv', $data['importFile']);
        static::assertSame(0, $data['position']);
        static::assertTrue($data['load']);

        // start image import
        $importController->importAction(new Request([
            'profileId' => $data['profileId'],
            'importFile' => $data['importFile'],
        ]));

        static::assertTrue($view->getAssign('success'));
        $data = $view->getAssign('data');
        static::assertIsArray($data);
        static::assertSame('ProductWIthImageImport.csv-articlesImages-swag.csv', $data['importFile']);
        static::assertSame(1, $data['position']);

        $csvContentWithPlaceholder = \str_replace($fixtureImagePath, '[placeholder_for_fixture_image]', (string) \file_get_contents($csvFile));
        \file_put_contents($csvFile, $csvContentWithPlaceholder);
    }

    private function getImportController(): Shopware_Controllers_Backend_SwagImportExportImport
    {
        $controller = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportImport::class);
        $controller->setContainer($this->getContainer());

        return $controller;
    }

    private function getUploadFileProvider(): UploadPathProvider
    {
        return $this->getContainer()->get(UploadPathProvider::class);
    }

    private function setImportBatchSize(): void
    {
        $this->getContainer()->get('config_writer')->save('batch-size-import', 1, 'SwagImportExport');
        $this->getContainer()->get(\Zend_Cache_Core::class)->clean();
        $this->getContainer()->get(\Shopware_Components_Config::class)->setShop(Shopware()->Shop());
    }
}
