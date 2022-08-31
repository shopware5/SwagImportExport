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
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportImport;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\FrontendTestViewMock;
use Symfony\Component\HttpFoundation\Request;

class ProductImportTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public const FORMAT_XML = 'xml';
    public const FORMAT_CSV = 'csv';

    private const DEFAULT_ARTICLE_PROFILE_ID = '5';

    public function testImportProductImportFile(): void
    {
        $importController = $this->getImportController();
        $view = new FrontendTestViewMock();

        $importController->setView($view);

        copy(\ImportExportTestKernel::IMPORT_FILES_DIR . 'ArticleImport.xml', $this->getUploadFileProvider()->getPath() . '/ArticleImport.xml');

        $importController->prepareImportAction(new Request([
            'profileId' => self::DEFAULT_ARTICLE_PROFILE_ID,
            'importFile' => 'ArticleImport.xml',
        ]));

        static::assertEquals(2, $view->getAssign('count'));
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
}
