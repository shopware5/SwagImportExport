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

class ArticlePricesExportTest extends \Enlight_Components_Test_Controller_TestCase
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

    public function test_articles_prices_xml_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_PRICES_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertPriceAttributeInXml($file, 'SW10002.1', 'price', '59.99');
        $this->assertPriceAttributeInXml($file, 'SW10002.1', '_name', 'Münsterländer Lagerkorn 32%');
        $this->assertPriceAttributeInXml($file, 'SW10002.1', '_additionaltext', '1,5 Liter');
    }

    public function test_articles_prices_csv_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_PRICES_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedPriceList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        $this->assertEquals('59.99', $mappedPriceList['SW10002.1']['price']);
        $this->assertEquals('Münsterländer Lagerkorn 32%', $mappedPriceList['SW10002.1']['_name']);
        $this->assertEquals('1,5 Liter', $mappedPriceList['SW10002.1']['_additionaltext']);
    }

    /**
     * @param string $filePath
     * @param string $orderNumber
     * @param string $attribute
     * @param string $expected
     */
    private function assertPriceAttributeInXml($filePath, $orderNumber, $attribute, $expected)
    {
        $articleDomNodeList = $this->queryXpath($filePath, "//Price[ordernumber='{$orderNumber}']/{$attribute}");
        $nodeValue = $articleDomNodeList->item(0)->nodeValue;
        $this->assertEquals($expected, $nodeValue);
    }
}
