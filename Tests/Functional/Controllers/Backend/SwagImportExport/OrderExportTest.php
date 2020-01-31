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
    use FixturesImportTrait;
    use DatabaseTestCaseTrait;
    use ExportControllerTrait;

    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    const PAYMENT_STATE_OPEN = '17';
    const ORDER_STATE_OPEN = '0';

    public function setUp(): void
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', '15');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentStatusId', '17');
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        static::assertEquals('15', $mappedOrderList[20001]['orderId']);
        static::assertEquals('17', $mappedOrderList[20001]['paymentStatusId']);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', 15);
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentStatusId', '17');
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        static::assertEquals('17', $mappedOrderList[20002]['paymentStatusId']);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', '15');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentStatusId', '17');
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        static::assertEquals(self::PAYMENT_STATE_OPEN, $mappedOrderList[20002]['paymentStatusId']);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $notExistingEntry = $this->queryXpath($file, '//order[number=20001]');
        static::assertEquals(0, $notExistingEntry->length);

        $this->assertOrderAttributeInXmlFile($file, '20002', 'paymentStatusId', '17');
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        static::assertEquals('17', $mappedOrderList[20002]['paymentStatusId']);
        static::assertEquals('57', $mappedOrderList[20002]['orderId']);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20001', 'orderId', '15');
        $this->assertOrderAttributeInXmlFile($file, '20001', 'paymentStatusId', '17');
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertOrderAttributeInXmlFile($file, '20002', 'orderId', '57');
        $this->assertOrderAttributeInXmlFile($file, '20002', 'paymentStatusId', '17');
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedOrderList = $this->readCsvMappedByNumber($file);
        static::assertEquals('57', $mappedOrderList[20002]['orderId']);
        static::assertEquals('17', $mappedOrderList[20002]['paymentStatusId']);
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
        static::assertEquals($expected, $nodeValue);
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
