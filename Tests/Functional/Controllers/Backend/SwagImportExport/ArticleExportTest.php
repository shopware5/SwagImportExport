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

class ArticleExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use ExportControllerTrait;
    use FixturesImportTrait;
    use DatabaseTestCaseTrait;

    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    const CATEGORY_ID_VINTAGE = 31;
    const PRODUCT_STREAM_ID = 999999;

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
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

        static::assertFileExists($file, "File not found {$fileName}");
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $exportedArticles = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals('Kobra Vodka 37,5%', $exportedArticles['SW10012']['name']);
        static::assertEquals('60', $exportedArticles['SW10012']['instock']);
        static::assertEquals('Feinbrennerei Sasse', $exportedArticles['SW10012']['supplier']);

        static::assertEquals('Special Finish Lagerkorn X.O. 32%', $exportedArticles['SW10009']['name']);
        static::assertEquals('12', $exportedArticles['SW10009']['instock']);
        static::assertEquals('Feinbrennerei Sasse', $exportedArticles['SW10009']['supplier']);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $articleList = $this->queryXpath($file, '//article');
        static::assertEquals($limit, $articleList->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount($limit, $mappedArticleList);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $articleListWithOffset = $this->queryXpath($file, '//article');
        static::assertEquals(15, $articleListWithOffset->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount(15, $mappedArticleList);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        //Fetch all article nodes with given category
        $articleDomNodeList = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $articleDomNodeList->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount(10, $mappedArticleList);
    }

    public function test_product_xml_export_with_product_stream_filter()
    {
        $productStreamId = self::PRODUCT_STREAM_ID;

        $this->addProductStream();
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['productStreamId'] = $productStreamId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $productList = $this->queryXpath($file, '//article');
        static::assertEquals(12, $productList->length);
    }

    public function test_product_csv_export_with_product_stream_filter()
    {
        $productStreamId = self::PRODUCT_STREAM_ID;

        $this->addProductStream();
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLE_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['productStreamId'] = $productStreamId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount(12, $mappedProductList);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $variantList = $this->queryXpath($file, "//article[mainnumber='SW10002.3']");
        static::assertEquals(3, $variantList->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedArticleList['SW10002.3']['name']);
        static::assertEquals('0,5 Liter', $mappedArticleList['SW10002.3']['additionalText']);

        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedArticleList['SW10002.1']['name']);
        static::assertEquals('1,5 Liter', $mappedArticleList['SW10002.1']['additionalText']);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $variantListWithLimit = $this->queryXpath($file, '//article');
        static::assertEquals($limit, $variantListWithLimit->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedArticleList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount($limit, $mappedArticleList);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $variantsNodeListWithOffset = $this->queryXpath($file, '//article');
        static::assertEquals(20, $variantsNodeListWithOffset->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedVariantList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertCount(20, $mappedVariantList);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        //Fetch all article nodes with given category
        $variantNodeListWithCategoryFilter = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $variantNodeListWithCategoryFilter->length);
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

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedVariantList = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertCount(10, $mappedVariantList);
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
