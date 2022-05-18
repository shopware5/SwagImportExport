<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend\SwagImportExport;

use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;

class OrderExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use FixturesImportTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;
    use ExportControllerTrait;

    public const FORMAT_XML = 'xml';
    public const FORMAT_CSV = 'csv';

    public const PAYMENT_STATE_OPEN = '17';
    public const ORDER_STATE_OPEN = '0';

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testOrdersXmlExport()
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

    public function testOrdersCsvExport()
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

    public function testOrdersXmlExportWithOrderstateFilter()
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

    public function testOrdersCsvExportWithOrderstateFilter()
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

    public function testOrdersXmlExportWithPaymentstateFilter()
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

    public function testOrdersCsvExportWithPaymentstateFilter()
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

    public function testOrdersXmlExportWithOrdernumberFromFilter()
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

    public function testOrdersCsvExportWithOrdernumberFromFilter()
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

    public function testOrdersXmlExportWithDateToFilter()
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

    public function testOrdersCsvExportWithDateToFilter()
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

    public function testOrdersXmlExportWithDateFromFilter()
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

    public function testOrdersCsvExportWithDateFromFilter()
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
