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

class CustomerExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use FixturesImportTrait;
    use DatabaseTestCaseTrait;
    use ExportControllerTrait;

    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function test_customer_xml_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::CUSTOMER_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertCustomerAttributeInXml($file, '20001', 'email', 'test@example.com');
        $this->assertCustomerAttributeInXml($file, '20001', 'billing_company', 'Muster GmbH');
        $this->assertCustomerAttributeInXml($file, '20001', 'shipping_company', 'shopware AG');
        $this->assertCustomerAttributeInXml($file, '20001', 'newsletter', '0');
    }

    public function test_customer_csv_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::CUSTOMER_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedCustomerList = $this->csvToArrayIndexedByFieldValue($file, 'customernumber');
        static::assertEquals('test@example.com', $mappedCustomerList[20001]['email']);
        static::assertEquals('Muster GmbH', $mappedCustomerList[20001]['billing_company']);
        static::assertEquals('shopware AG', $mappedCustomerList[20001]['shipping_company']);
        static::assertEquals(0, $mappedCustomerList[20001]['newsletter']);
    }

    /**
     * @param string $filePath
     * @param string $customerNumber
     * @param string $attribute
     * @param string $expected
     */
    private function assertCustomerAttributeInXml($filePath, $customerNumber, $attribute, $expected)
    {
        $customerDomNodeList = $this->queryXpath($filePath, "//customer[customernumber='{$customerNumber}']/{$attribute}");
        $nodeValue = $customerDomNodeList->item(0)->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }
}
