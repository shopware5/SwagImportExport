<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class ProductsInstockExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use FixturesImportTrait;
    use DatabaseTestCaseTrait;
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

    public function testProductsInstockXmlExport(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10023', 'instock', '0');
        $this->assertProductAttributeInXml($file, 'SW10023', '_price', '35');
    }

    public function testProductsInstockCsvExport(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(0, $mappedProductInstockList['SW10023']['instock']);
        static::assertEquals(35, $mappedProductInstockList['SW10023']['_price']);
    }

    public function testProductsInstockXmlExportWithInstockFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'inStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $productNodeList = $this->queryXpath($file, '//article');
        static::assertEquals(342, $productNodeList->length);
        $this->assertProductAttributeInXml($file, 'SW10003', 'instock', '25');
        $this->assertProductAttributeInXml($file, 'SW10003', '_price', '14.95');
    }

    public function testProductsInstockCsvExportWithInstockFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'inStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(25, $mappedProductInstockList['SW10003']['instock']);
        static::assertEquals(14.95, $mappedProductInstockList['SW10003']['_price']);
    }

    public function testProductsInstockXmlExportWithNotInstockFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10023', '_price', '35');
        $this->assertProductAttributeInXml($file, 'SW10023', 'instock', '0');
        $this->assertProductAttributeInXml($file, 'SW10023', '_supplier', 'Teapavilion');
    }

    public function testProductsInstockCsvExportWithNotInstockFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(35, $mappedProductInstockList['SW10023']['_price']);
        static::assertEquals(0, $mappedProductInstockList['SW10023']['instock']);
        static::assertEquals('Teapavilion', $mappedProductInstockList['SW10023']['_supplier']);
    }

    public function testProductsInstockXmlExportWithInStockOnSaleFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'inStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10082', 'instock', '5');
        $this->assertProductAttributeInXml($file, 'SW10082', '_supplier', 'Das blaue Haus');
        $this->assertProductAttributeInXml($file, 'SW10082', '_price', '7.99');
    }

    public function testProductsInstockCsvExportWithInStockOnSaleFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'inStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals(5, $mappedProductList['SW10082']['instock']);
        static::assertEquals('Das blaue Haus', $mappedProductList['SW10082']['_supplier']);
        static::assertEquals(7.99, $mappedProductList['SW10082']['_price']);
    }

    public function testProductsInstockXmlExportWithNotInStockOnSaleFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10198', 'instock', '0');
        $this->assertProductAttributeInXml($file, 'SW10198', '_price', '238');
        $this->assertProductAttributeInXml($file, 'SW10198', '_supplier', 'Example');
    }

    public function testProductsInstockCsvExportWithNotInStockOnSaleFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(0, $mappedProductInstockList['SW10198']['instock']);
        static::assertEquals(238, $mappedProductInstockList['SW10198']['_price']);
        static::assertEquals('Example', $mappedProductInstockList['SW10198']['_supplier']);
    }

    public function testProductsInstockXmlExportWithNotInStockMinStockFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStockMinStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10200', '_supplier', 'Example');
        $this->assertProductAttributeInXml($file, 'SW10200', 'instock', '0');
        $this->assertProductAttributeInXml($file, 'SW10200', '_price', '99');
    }

    public function testProductsInstockCsvExportNotInStockMinStockFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStockMinStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals('Example', $mappedProductList['SW10200']['_supplier']);
        static::assertEquals(0, $mappedProductList['SW10200']['instock']);
        static::assertEquals(99, $mappedProductList['SW10200']['_price']);
    }

    public function testProductsInstockXmlExportWithCustomInstockGreaterThanFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'custom';
        $params['customFilterValue'] = '150';
        $params['customFilterDirection'] = 'greaterThan';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10014', 'instock', '178');
        $this->assertProductAttributeInXml($file, 'SW10014', '_price', '3.8');
        $this->assertProductAttributeInXml($file, 'SW10014', '_supplier', 'Teapavilion');
    }

    public function testProductsInstockCsvExportWithCustomInstockGreaterThanFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'custom';
        $params['customFilterValue'] = '150';
        $params['customFilterDirection'] = 'greaterThan';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(178, $mappedProductInstockList['SW10014']['instock']);
        static::assertEquals(3.8, $mappedProductInstockList['SW10014']['_price']);
        static::assertEquals('Teapavilion', $mappedProductInstockList['SW10014']['_supplier']);
    }

    public function testProductsInstockXmlExportWithCustomInstockLowerThanFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'custom';
        $params['customFilterValue'] = '2';
        $params['customFilterDirection'] = 'lessThan';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10203.5', 'instock', '2');
        $this->assertProductAttributeInXml($file, 'SW10203.5', '_supplier', 'Example');
        $this->assertProductAttributeInXml($file, 'SW10203.5', '_price', '15');
    }

    public function testProductsInstockCsvExportWithCustomInstockLowerThanFilter(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCTS_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'custom';
        $params['customFilterValue'] = '2';
        $params['customFilterDirection'] = 'lessThan';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(2, $mappedProductInstockList['SW10203.5']['instock']);
        static::assertEquals('Example', $mappedProductInstockList['SW10203.5']['_supplier']);
        static::assertEquals(15, $mappedProductInstockList['SW10203.5']['_price']);
    }

    private function assertProductAttributeInXml(string $filePath, string $orderNumber, string $attribute, string $expected): void
    {
        $productDomNodeList = $this->queryXpath($filePath, "//article[ordernumber='{$orderNumber}']/{$attribute}");
        $node = $productDomNodeList->item(0);
        static::assertInstanceOf(\DOMNode::class, $node);
        $nodeValue = $node->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }

    private function readCsvIndexedByOrdernumber(string $file): array
    {
        return $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
    }
}
