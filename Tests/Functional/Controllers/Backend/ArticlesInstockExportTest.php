<?php
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

class ArticlesInstockExportTest extends \Enlight_Components_Test_Controller_TestCase
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

    public function testArticlesInstockXmlExport()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertArticleAttributeInXml($file, 'SW10023', 'instock', 0);
        $this->assertArticleAttributeInXml($file, 'SW10023', '_price', 35);
    }

    public function testArticlesInstockCsvExport()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(0, $mappedArticleInstockList['SW10023']['instock']);
        static::assertEquals(35, $mappedArticleInstockList['SW10023']['_price']);
    }

    public function testArticlesInstockXmlExportWithInstockFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'inStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $articleNodeList = $this->queryXpath($file, '//article');
        static::assertEquals(342, $articleNodeList->length);
        $this->assertArticleAttributeInXml($file, 'SW10003', 'instock', 25);
        $this->assertArticleAttributeInXml($file, 'SW10003', '_price', 14.95);
    }

    public function testArticlesInstockCsvExportWithInstockFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'inStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(25, $mappedArticleInstockList['SW10003']['instock']);
        static::assertEquals(14.95, $mappedArticleInstockList['SW10003']['_price']);
    }

    public function testArticlesInstockXmlExportWithNotInstockFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertArticleAttributeInXml($file, 'SW10023', '_price', 35);
        $this->assertArticleAttributeInXml($file, 'SW10023', 'instock', 0);
        $this->assertArticleAttributeInXml($file, 'SW10023', '_supplier', 'Teapavilion');
    }

    public function testArticlesInstockCsvExportWithNotInstockFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(35, $mappedArticleInstockList['SW10023']['_price']);
        static::assertEquals(0, $mappedArticleInstockList['SW10023']['instock']);
        static::assertEquals('Teapavilion', $mappedArticleInstockList['SW10023']['_supplier']);
    }

    public function testArticlesInstockXmlExportWithInStockOnSaleFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'inStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertArticleAttributeInXml($file, 'SW10082', 'instock', 5);
        $this->assertArticleAttributeInXml($file, 'SW10082', '_supplier', 'Das blaue Haus');
        $this->assertArticleAttributeInXml($file, 'SW10082', '_price', 7.99);
    }

    public function testArticlesInstockCsvExportWithInStockOnSaleFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'inStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals(5, $mappedArticleList['SW10082']['instock']);
        static::assertEquals('Das blaue Haus', $mappedArticleList['SW10082']['_supplier']);
        static::assertEquals(7.99, $mappedArticleList['SW10082']['_price']);
    }

    public function testArticlesInstockXmlExportWithNotInStockOnSaleFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertArticleAttributeInXml($file, 'SW10198', 'instock', 0);
        $this->assertArticleAttributeInXml($file, 'SW10198', '_price', 238);
        $this->assertArticleAttributeInXml($file, 'SW10198', '_supplier', 'Example');
    }

    public function testArticlesInstockCsvExportWithNotInStockOnSaleFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(0, $mappedArticleInstockList['SW10198']['instock']);
        static::assertEquals(238, $mappedArticleInstockList['SW10198']['_price']);
        static::assertEquals('Example', $mappedArticleInstockList['SW10198']['_supplier']);
    }

    public function testArticlesInstockXmlExportWithNotInStockMinStockFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStockMinStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertArticleAttributeInXml($file, 'SW10200', '_supplier', 'Example');
        $this->assertArticleAttributeInXml($file, 'SW10200', 'instock', 0);
        $this->assertArticleAttributeInXml($file, 'SW10200', '_price', 99);
    }

    public function testArticlesInstockCsvExportNotInStockMinStockFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStockMinStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals('Example', $mappedArticleList['SW10200']['_supplier']);
        static::assertEquals(0, $mappedArticleList['SW10200']['instock']);
        static::assertEquals(99, $mappedArticleList['SW10200']['_price']);
    }

    public function testArticlesInstockXmlExportWithCustomInstockGreaterThanFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
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

        $this->assertArticleAttributeInXml($file, 'SW10014', 'instock', 178);
        $this->assertArticleAttributeInXml($file, 'SW10014', '_price', 3.8);
        $this->assertArticleAttributeInXml($file, 'SW10014', '_supplier', 'Teapavilion');
    }

    public function testArticlesInstockCsvExportWithCustomInstockGreaterThanFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
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

        $mappedArticleInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(178, $mappedArticleInstockList['SW10014']['instock']);
        static::assertEquals(3.8, $mappedArticleInstockList['SW10014']['_price']);
        static::assertEquals('Teapavilion', $mappedArticleInstockList['SW10014']['_supplier']);
    }

    public function testArticlesInstockXmlExportWithCustomInstockLowerThanFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
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

        $this->assertArticleAttributeInXml($file, 'SW10203.5', 'instock', 2);
        $this->assertArticleAttributeInXml($file, 'SW10203.5', '_supplier', 'Example');
        $this->assertArticleAttributeInXml($file, 'SW10203.5', '_price', 15);
    }

    public function testArticlesInstockCsvExportWithCustomInstockLowerThanFilter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
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

        $mappedArticleInstockList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals(2, $mappedArticleInstockList['SW10203.5']['instock']);
        static::assertEquals('Example', $mappedArticleInstockList['SW10203.5']['_supplier']);
        static::assertEquals(15, $mappedArticleInstockList['SW10203.5']['_price']);
    }

    /**
     * @param string $filePath
     * @param string $orderNumber
     * @param string $attribute
     * @param string $expected
     */
    private function assertArticleAttributeInXml($filePath, $orderNumber, $attribute, $expected)
    {
        $articleDomNodeList = $this->queryXpath($filePath, "//article[ordernumber='{$orderNumber}']/{$attribute}");
        $nodeValue = $articleDomNodeList->item(0)->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function readCsvIndexedByOrdernumber($file)
    {
        return $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
    }
}
