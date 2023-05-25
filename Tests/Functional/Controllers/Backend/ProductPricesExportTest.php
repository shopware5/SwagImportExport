<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\Utils\SwagVersionHelper;
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportExport;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use SwagImportExport\Tests\Helper\TestViewMock;

class ProductPricesExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use FixturesImportTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;
    use ExportControllerTrait;

    public const FORMAT_XML = 'xml';
    public const FORMAT_CSV = 'csv';

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testProductsPricesXmlExport(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_PRICES_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = 1;

        $controller = $this->createController();
        $view = new TestViewMock();
        $controller->setView($view);
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($params);
        $controller->setRequest($request);

        $controller->exportAction();

        $assigned = $view->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertPriceAttributeInXml($file, 'SW10002.1', 'price', '59.99');
        $this->assertPriceAttributeInXml($file, 'SW10002.1', '_name', 'Münsterländer Lagerkorn 32%');
        $this->assertPriceAttributeInXml($file, 'SW10002.1', '_additionaltext', '1,5 Liter');
    }

    public function testProductsPricesCsvExport(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_PRICES_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = 1;

        $controller = $this->createController();
        $view = new TestViewMock();
        $controller->setView($view);
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($params);
        $controller->setRequest($request);

        $controller->exportAction();

        $assigned = $view->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedPriceList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals('59.99', $mappedPriceList['SW10002.1']['price']);
        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedPriceList['SW10002.1']['_name']);
        static::assertEquals('1,5 Liter', $mappedPriceList['SW10002.1']['_additionaltext']);
    }

    public function testProductsPricesCsvExportHasNotEmptyValues(): void
    {
        $params = $this->getExportRequestParams();
        $nonEmptyColumns = include __DIR__ . '/_fixtures/product.prices.non.empty.columns.php';
        if (SwagVersionHelper::isShopware578()) {
            $nonEmptyColumns[] = 'regulationPrice';
        }

        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_PRICES_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = 1;
        $params['limit'] = 1;

        $controller = $this->createController();
        $view = new TestViewMock();
        $controller->setView($view);
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->setParams($params);
        $controller->setRequest($request);

        $controller->exportAction();

        $data = $view->getAssign('data');

        $fileName = $data['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, 'File not found ' . $fileName);
        $this->backendControllerTestHelper->addFile($file);
        $mappedProductPriceList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');

        foreach ($nonEmptyColumns as $column) {
            static::assertStringMatchesFormat('%s', $mappedProductPriceList['SW10003'][$column], 'empty value returned for ' . $column);
        }
    }

    private function assertPriceAttributeInXml(string $filePath, string $orderNumber, string $attribute, string $expected): void
    {
        $productDomNodeList = $this->queryXpath($filePath, "//Price[ordernumber='{$orderNumber}']/{$attribute}");
        $node = $productDomNodeList->item(0);
        static::assertInstanceOf(\DOMNode::class, $node);
        $nodeValue = $node->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }

    private function createController(): Shopware_Controllers_Backend_SwagImportExportExport
    {
        $controller = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportExport::class);
        $controller->setContainer($this->getContainer());

        return $controller;
    }
}
