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
use SwagImportExport\Controllers\Backend\Shopware_Controllers_Backend_SwagImportExportExport;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use SwagImportExport\Tests\Helper\ReflectionHelperTrait;
use SwagImportExport\Tests\Helper\TestViewMock;

class ProductExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use ExportControllerTrait;
    use FixturesImportTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;
    use ReflectionHelperTrait;

    public const FORMAT_XML = 'xml';
    public const FORMAT_CSV = 'csv';

    public const CATEGORY_ID_VINTAGE = 31;
    public const PRODUCT_STREAM_ID = 999999;

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testProductXmlExport(): void
    {
        $params = $this->getExportRequestParams();

        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;

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

        $this->assertProductAttributeInXml($file, 'SW10012', 'name', 'Kobra Vodka 37,5%');
        $this->assertProductAttributeInXml($file, 'SW10012', 'instock', '60');
        $this->assertProductAttributeInXml($file, 'SW10012', 'supplier', 'Feinbrennerei Sasse');

        $this->assertProductAttributeInXml($file, 'SW10009', 'name', 'Special Finish Lagerkorn X.O. 32%');
        $this->assertProductAttributeInXml($file, 'SW10009', 'instock', '12');
        $this->assertProductAttributeInXml($file, 'SW10009', 'supplier', 'Feinbrennerei Sasse');
    }

    public function testProductCsvExport(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;

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

        static::assertFileExists($file, sprintf('File not found %s', $fileName));
        $this->backendControllerTestHelper->addFile($file);

        $exportedProducts = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals('Kobra Vodka 37,5%', $exportedProducts['SW10012']['name']);
        static::assertEquals('60', $exportedProducts['SW10012']['instock']);
        static::assertEquals('Feinbrennerei Sasse', $exportedProducts['SW10012']['supplier']);

        static::assertEquals('Special Finish Lagerkorn X.O. 32%', $exportedProducts['SW10009']['name']);
        static::assertEquals('12', $exportedProducts['SW10009']['instock']);
        static::assertEquals('Feinbrennerei Sasse', $exportedProducts['SW10009']['supplier']);
    }

    public function testProductXmlExportWithLimit(): void
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['limit'] = $limit;

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

        $productList = $this->queryXpath($file, '//article');
        static::assertEquals($limit, $productList->length);
    }

    public function testProductCsvExportWithLimit(): void
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['limit'] = $limit;

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

        $mappedProductList = $this->readCsvIndexedByOrderNumber($file);
        static::assertCount($limit, $mappedProductList);
    }

    public function testProductXmlExportWithOffset210(): void
    {
        $offset = 210;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['offset'] = $offset;

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

        $productListWithOffset = $this->queryXpath($file, '//article');
        static::assertEquals(15, $productListWithOffset->length);
    }

    public function testProductCsvExportWithOffset210(): void
    {
        $offset = 210;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['offset'] = $offset;

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

        $mappedProductList = $this->readCsvIndexedByOrderNumber($file);
        static::assertCount(15, $mappedProductList);
    }

    public function testProductXmlExportWithCategoryFilter(): void
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['categories'] = $categoryId;

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

        // Fetch all article nodes with given category
        $productDomNodeList = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $productDomNodeList->length);
    }

    public function testProductCsvExportWithCategoryFilter(): void
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['categories'] = $categoryId;

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

        $mappedProductList = $this->readCsvIndexedByOrderNumber($file);
        static::assertCount(10, $mappedProductList);
    }

    public function testProductXmlExportWithProductStreamFilter(): void
    {
        $productStreamId = self::PRODUCT_STREAM_ID;

        $this->addProductStream();
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['productStreamId'] = $productStreamId;

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

        $productList = $this->queryXpath($file, '//article');
        static::assertEquals(12, $productList->length);
    }

    public function testProductCsvExportWithProductStreamFilter(): void
    {
        $productStreamId = self::PRODUCT_STREAM_ID;

        $this->addProductStream();
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['productStreamId'] = $productStreamId;

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

        $mappedProductList = $this->readCsvIndexedByOrderNumber($file);
        static::assertCount(12, $mappedProductList);
    }

    public function testVariantXmlExport(): void
    {
        $exportVariants = 'true';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $exportVariants;

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

        $variantList = $this->queryXpath($file, "//article[mainnumber='SW10002.3']");
        static::assertEquals(3, $variantList->length);
    }

    public function testVariantCsvExport(): void
    {
        $exportVariants = 'true';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;

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

        $mappedProductList = $this->readCsvIndexedByOrderNumber($file);
        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedProductList['SW10002.3']['name']);
        static::assertEquals('0,5 Liter', $mappedProductList['SW10002.3']['additionalText']);

        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedProductList['SW10002.1']['name']);
        static::assertEquals('1,5 Liter', $mappedProductList['SW10002.1']['additionalText']);
    }

    public function testVariantXmlExportWithLimit(): void
    {
        $exportVariants = 'true';
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::VARIANT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $exportVariants;
        $params['limit'] = $limit;

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

        $variantListWithLimit = $this->queryXpath($file, '//article');
        static::assertEquals($limit, $variantListWithLimit->length);
    }

    public function testVariantCsvExportWithLimit(): void
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::VARIANT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = 'true';
        $params['limit'] = $limit;

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

        $mappedProductList = $this->readCsvIndexedByOrderNumber($file);
        static::assertCount($limit, $mappedProductList);
    }

    public function testVariantXmlExportWithOffset(): void
    {
        $variants = 'true';
        $offset = '380';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $variants;
        $params['offset'] = $offset;

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

        $variantsNodeListWithOffset = $this->queryXpath($file, '//article');
        static::assertEquals(20, $variantsNodeListWithOffset->length);
    }

    public function testVariantCsvExportWithOffset(): void
    {
        $exportVariants = 'true';
        $offset = '380';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $params['offset'] = $offset;

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

        $mappedVariantList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertCount(20, $mappedVariantList);
    }

    public function testVariantXmlExportWithCategoryFilter(): void
    {
        $exportVariants = 'true';
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $exportVariants;
        $params['categories'] = $categoryId;

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

        // Fetch all article nodes with given category
        $variantNodeListWithCategoryFilter = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $variantNodeListWithCategoryFilter->length);
    }

    public function testVariantCsvExportWithCategoryFilter(): void
    {
        $exportVariants = 'true';
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $params['categories'] = $categoryId;

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

        $mappedVariantList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertCount(10, $mappedVariantList);
    }

    public function testProductCsvExportHasNotEmptyValues(): void
    {
        $params = $this->getExportRequestParams();
        $nonEmptyColumns = include __DIR__ . '/_fixtures/product.export.non.empty.columns.php';
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    public function testProductXmlExportWithCategoryFilterWithBatchSize(): void
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $this->setConfig('batch-size-export', 1);

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        for ($position = 1; $position <= 10; ++$position) {
            $this->dispatch('backend/SwagImportExportExport/export');
            $assigned = $this->View()->getAssign('data');
            static::assertSame($position, $assigned['position']);
            $this->Request()->setParams($assigned);
        }

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        // Fetch all article nodes with given category
        $productDomNodeList = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $productDomNodeList->length);
    }

    private function assertProductAttributeInXml(string $filePath, string $orderNumber, string $attribute, string $expected): void
    {
        $productDomNodeList = $this->queryXpath($filePath, "//article[ordernumber='{$orderNumber}']/{$attribute}");
        $node = $productDomNodeList->item(0);
        static::assertInstanceOf(\DOMNode::class, $node);
        $nodeValue = $node->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function readCsvIndexedByOrderNumber(string $file): array
    {
        return $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
    }

    private function createController(): Shopware_Controllers_Backend_SwagImportExportExport
    {
        $controller = $this->getContainer()->get(Shopware_Controllers_Backend_SwagImportExportExport::class);
        $controller->setContainer($this->getContainer());

        return $controller;
    }
}
