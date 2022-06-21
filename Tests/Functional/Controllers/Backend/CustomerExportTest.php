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

class CustomerExportTest extends \Enlight_Components_Test_Controller_TestCase
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

    public function testCustomerXmlExport(): void
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

    public function testCustomerCsvExport(): void
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

    private function assertCustomerAttributeInXml(string $filePath, string $customerNumber, string $attribute, string $expected): void
    {
        $customerDomNodeList = $this->queryXpath($filePath, "//customer[customernumber='{$customerNumber}']/{$attribute}");
        $nodeValue = $customerDomNodeList->item(0)->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }
}
