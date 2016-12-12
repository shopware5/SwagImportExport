<?php


namespace SwagImportExport\Tests\Functional\Controllers\Backend\SwagImportExport;

use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use SwagImportExport\Tests\Helper\ExportControllerTrait;

class NewsletterExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use DatabaseTestCaseTrait;
    use FixturesImportTrait;
    use ExportControllerTrait;

    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    public function setUp()
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function test_newsletter_xml_export()
    {
        $this->importNewsletterDemoData();

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::NEWSLETTER_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $newsletterNodeList = $this->queryXpath($file, "//user");
        $this->assertEquals(25, $newsletterNodeList->length);

        $newsletterNodeList = $this->queryXpath($file, "//user[email='test_0@example.com']/email");
        $this->assertEquals('test_0@example.com', $newsletterNodeList->item(0)->nodeValue);
    }

    public function test_newsletter_csv_export()
    {
        $this->importNewsletterDemoData();

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::NEWSLETTER_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedNewsletterList = $this->csvToArrayIndexedByFieldValue($file, 'email');
        $this->assertEquals('test_0@example.com', $mappedNewsletterList['test_0@example.com']['email']);
        $this->assertCount(25, $mappedNewsletterList);
    }
}
