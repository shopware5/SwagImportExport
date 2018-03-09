<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend\SwagImportExport;

use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class OrderExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use DatabaseTestCaseTrait;
    use FixturesImportTrait;
    use ExportControllerTrait;

    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    const PAYMENT_STATE_OPEN = '17';
    const ORDER_STATE_OPEN = '0';

    public function setUp()
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function test_orders_xml_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', '15');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentID', '4');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'invoiceAmount', '998.56');
    }

    public function test_orders_csv_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        $this->assertEquals('15', $mappedOrderList[20001]['orderId']);
        $this->assertEquals('4', $mappedOrderList[20001]['paymentID']);
        $this->assertEquals('998.56', $mappedOrderList[20001]['invoiceAmount']);
    }

    public function test_orders_xml_export_with_orderstate_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['orderstate'] = self::ORDER_STATE_OPEN;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', 15);
        $this->assertOrderAttributeInXmlFile($file, '20001', 'invoiceAmount', '998.56');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentID', '4');
    }

    public function test_orders_csv_export_with_orderstate_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['orderstate'] = self::ORDER_STATE_OPEN;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        $this->assertEquals(self::ORDER_STATE_OPEN, $mappedOrderList[20002]['orderStatusID']);
    }

    public function test_orders_xml_export_with_paymentstate_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['paymentstate'] = self::PAYMENT_STATE_OPEN;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'invoiceAmount', '998.56');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', '15');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentID', '4');
    }

    public function test_orders_csv_export_with_paymentstate_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['paymentstate'] = self::PAYMENT_STATE_OPEN;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        $this->assertEquals(self::PAYMENT_STATE_OPEN, $mappedOrderList[20002]['cleared']);
    }

    public function test_orders_xml_export_with_ordernumber_from_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['ordernumberFrom'] = '20001';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $notExistingEntry = $this->queryXpath($file, '//order[number=20001]');
        $this->assertEquals(0, $notExistingEntry->length);

        $this->assertOrderAttributeInXmlFile($file, '20002', 'paymentID', '4');
        $this->assertOrderAttributeInXmlFile($file, '20002', 'invoiceAmount', '201.86');
        $this->assertOrderAttributeInXmlFile($file, '20002', 'orderId', '57');
    }

    public function test_orders_csv_export_with_ordernumber_from_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['ordernumberFrom'] = '20001';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        $this->assertEquals('4', $mappedOrderList[20002]['paymentID']);
        $this->assertEquals('57', $mappedOrderList[20002]['orderId']);
        $this->assertEquals('201.86', $mappedOrderList[20002]['invoiceAmount']);
    }

    public function test_orders_xml_export_with_date_to_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['dateTo'] = '30.08.2012';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'invoiceAmount', '998.56');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', '15');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentID', '4');
    }

    public function test_orders_csv_export_with_date_to_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['dateTo'] = '30.08.2012';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        $this->assertEquals('2012-08-30 10:15:54', $mappedOrderList[20001]['orderTime']);
    }

    public function test_orders_xml_export_with_date_from_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['dateFrom'] = '31.08.2012';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20002', 'orderId', '57');
        $this->assertOrderAttributeInXmlFile($file, '20002', 'invoiceAmount', '201.86');
        $this->assertOrderAttributeInXmlFile($file, '20002', 'paymentID', '4');
    }

    public function test_orders_csv_export_with_date_from_filter()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ORDERS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['dateFrom'] = '31.08.2012';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        $this->assertEquals('57', $mappedOrderList[20002]['orderId']);
        $this->assertEquals('201.86', $mappedOrderList[20002]['invoiceAmount']);
        $this->assertEquals('4', $mappedOrderList[20002]['paymentID']);
        $this->assertEquals('2012-08-31 08:51:46', $mappedOrderList[20002]['orderTime']);
    }

    /**
     * @param string $filePath
     * @param string $number
     * @param string $attribute
     * @param string $expected
     */
    private function assertOrderAttributeInXmlFile($filePath, $number, $attribute, $expected)
    {
        $orderDomNodeList = $this->queryXpath($filePath, "//order[number='{$number}']/{$attribute}");
        $nodeValue = $orderDomNodeList->item(0)->nodeValue;
        $this->assertEquals($expected, $nodeValue);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    private function readCsvMappedByNumber($file)
    {
        return $this->csvToArrayIndexedByFieldValue($file, 'number');
    }
}
