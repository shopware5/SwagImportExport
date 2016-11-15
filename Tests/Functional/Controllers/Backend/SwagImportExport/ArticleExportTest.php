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
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use SwagImportExport\Tests\Helper\ExportControllerTrait;

class ArticleExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use DatabaseTestCaseTrait;
    use FixturesImportTrait;
    use ExportControllerTrait;

    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    const CATEGORY_ID_VINTAGE = 31;

    public function setUp()
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
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
        $this->assertEquals($expected, $nodeValue);
    }

    /**
     * @param string $file
     * @return array
     */
    private function readCsvIndexedByOrdernumber($file)
    {
        return $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
    }

    public function test_article_xml_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertArticleAttributeInXml($file, 'SW10012', 'name', 'Kobra Vodka 37,5%');
        $this->assertArticleAttributeInXml($file, 'SW10012', 'instock', '60');
        $this->assertArticleAttributeInXml($file, 'SW10012', 'supplier', 'Feinbrennerei Sasse');

        $this->assertArticleAttributeInXml($file, 'SW10009', 'name', 'Special Finish Lagerkorn X.O. 32%');
        $this->assertArticleAttributeInXml($file, 'SW10009', 'instock', '12');
        $this->assertArticleAttributeInXml($file, 'SW10009', 'supplier', 'Feinbrennerei Sasse');
    }

    public function test_article_csv_export()
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $exportedArticles = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        $this->assertEquals('Kobra Vodka 37,5%', $exportedArticles['SW10012']['name']);
        $this->assertEquals('60', $exportedArticles['SW10012']['instock']);
        $this->assertEquals('Feinbrennerei Sasse', $exportedArticles['SW10012']['supplier']);

        $this->assertEquals('Special Finish Lagerkorn X.O. 32%', $exportedArticles['SW10009']['name']);
        $this->assertEquals('12', $exportedArticles['SW10009']['instock']);
        $this->assertEquals('Feinbrennerei Sasse', $exportedArticles['SW10009']['supplier']);
    }

    public function test_article_xml_export_with_limit()
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $articleList = $this->queryXpath($file, '//article');
        $this->assertEquals($limit, $articleList->length);
    }

    public function test_article_csv_export_with_limit()
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        $this->assertCount($limit, $mappedArticleList);
    }

    public function test_article_xml_export_with_offset_210()
    {
        $offset = 210;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['offset'] = $offset;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $articleListWithOffset = $this->queryXpath($file, '//article');
        $this->assertEquals(15, $articleListWithOffset->length);
    }

    public function test_article_csv_export_with_offset_210()
    {
        $offset = 210;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['offset'] = $offset;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        $this->assertCount(15, $mappedArticleList);
    }

    public function test_article_xml_export_with_category_filter()
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        //Fetch all article nodes with given category
        $articleDomNodeList = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        $this->assertEquals(10, $articleDomNodeList->length);
    }

    public function test_article_csv_export_with_category_filter()
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        $this->assertCount(10, $mappedArticleList);
    }

    public function test_variant_xml_export()
    {
        $exportVariants = 'true';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $exportVariants;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $variantList = $this->queryXpath($file, "//article[mainnumber='SW10002.3']");
        $this->assertEquals(3, $variantList->length);
    }

    public function test_variant_csv_export()
    {
        $exportVariants = 'true';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        $this->assertEquals('Münsterländer Lagerkorn 32%', $mappedArticleList['SW10002.3']['name']);
        $this->assertEquals('0,5 Liter', $mappedArticleList['SW10002.3']['additionalText']);

        $this->assertEquals('Münsterländer Lagerkorn 32%', $mappedArticleList['SW10002.1']['name']);
        $this->assertEquals('1,5 Liter', $mappedArticleList['SW10002.1']['additionalText']);
    }

    public function test_variant_xml_export_with_limit()
    {
        $exportVariants = 'true';
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::VARIANT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $exportVariants;
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $variantListWithLimit = $this->queryXpath($file, "//article");
        $this->assertEquals($limit, $variantListWithLimit->length);
    }

    public function test_variant_csv_export_with_limit()
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::VARIANT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = 'true';
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        $this->assertCount($limit, $mappedArticleList);
    }

    public function test_variant_xml_export_with_offset()
    {
        $variants = 'true';
        $offset = '380';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $variants;
        $params['offset'] = $offset;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $variantsNodeListWithOffset = $this->queryXpath($file, "//article");
        $this->assertEquals(20, $variantsNodeListWithOffset->length);
    }

    public function test_variant_csv_export_with_offset()
    {
        $exportVariants = 'true';
        $offset = '380';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $params['offset'] = $offset;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedVariantList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        $this->assertCount(20, $mappedVariantList);
    }

    public function test_variant_xml_export_with_category_filter()
    {
        $exportVariants = 'true';
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['variants'] = $exportVariants;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        //Fetch all article nodes with given category
        $variantNodeListWithCategoryFilter = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        $this->assertEquals(10, $variantNodeListWithCategoryFilter->length);
    }

    public function test_variant_csv_export_with_category_filter()
    {
        $exportVariants = 'true';
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedVariantList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        $this->assertCount(10, $mappedVariantList);
    }
}
