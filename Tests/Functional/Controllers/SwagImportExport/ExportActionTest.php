<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\SwagImportExport;

use Shopware\Components\SwagImportExport\UploadPathProvider;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use Tests\Helper\BackendControllerTestHelper;

class ExportActionTest extends \Enlight_Components_Test_Controller_TestCase
{
    const FORMAT_XML = 'xml';
    const FORMAT_CSV = 'csv';

    const PAYMENT_STATE_OPEN = '17';
    const ORDER_STATE_OPEN = '0';
    const CATEGORY_ID_VINTAGE = 31;

    /**
     * @var BackendControllerTestHelper
     */
    private $backendControllerTestHelper;

    /**
     * @var UploadPathProvider
     */
    private $uploadPathProvider;

    public function setUp()
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();

        $this->backendControllerTestHelper = Shopware()->Container()->get('swag_import_export.tests.backend_controller_test_helper');
        $this->backendControllerTestHelper->setUp();

        $this->uploadPathProvider = Shopware()->Container()->get('swag_import_export.upload_path_provider');
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->backendControllerTestHelper->tearDown();
    }

    public function test_article_xml_export()
    {
        $expectedLineAmount = 14223;

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

        $actualLineAmount = $this->getLineAmount($file);
        $this->assertEquals($expectedLineAmount, $actualLineAmount, "Expected {$expectedLineAmount} lines for file {$fileName} but counted only {$actualLineAmount}.");
    }

    public function test_article_csv_export()
    {
        $expectedLineAmount = 290;

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

        $actualLineAmount = $this->getLineAmount($file);
        $this->assertEquals($expectedLineAmount, $actualLineAmount, "Expected {$expectedLineAmount} lines for file {$fileName} but counted only {$actualLineAmount}.");
    }

    public function test_article_xml_export_with_limit()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_limit_10.xml';
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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_article_csv_export_with_limit()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_limit_10.csv';
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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_article_xml_export_with_offset_210()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_with_offset_210.xml';
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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_article_csv_export_with_offset_210()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_with_offset_210.csv';
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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_article_csv_export_with_category_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR. '/export_articles_with_category_filter.csv';
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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_article_xml_export_with_category_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR. '/export_articles_with_category_filter.xml';
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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_variant_xml_export()
    {
        $expectedLineAmount = 25891;
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

        $this->assertEquals($expectedLineAmount, $this->getLineAmount($file));
    }

    public function test_variant_csv_export()
    {
        $expectedLineAmount = 525;
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

        $this->assertEquals($expectedLineAmount, $this->getLineAmount($file));
    }

    public function test_variant_xml_export_with_limit()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_variants_limit_10.xml';
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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_variant_csv_export_with_limit()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_variants_limit_10.csv';
        $exportVariants = 'true';
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::VARIANT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_variant_xml_export_with_offset()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_variants_offset_380.xml';
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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_variant_csv_export_with_offset()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_variants_offset_380.csv';
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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_variant_csv_export_with_category_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_variants_with_category_filter.csv';
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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_variant_xml_export_with_category_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_variants_with_category_filter.xml';
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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_customer_xml_export()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_customers.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::CUSTOMER_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_customer_csv_export()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_customers.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::CUSTOMER_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_category_xml_export()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_categories.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::CATEGORY_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_category_csv_export()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_categories.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::CATEGORY_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_instock_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_instock_filter.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'inStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_with_instock_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_instock_filter.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'inStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_not_instock_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_not_instock_filter.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_with_not_instock_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_not_instock_filter.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_in_stock_on_sale_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_in_stock_on_sale_filter.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'inStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_with_in_stock_on_sale_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_in_stock_on_sale_filter.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'inStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_not_in_stock_on_sale_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_not_in_stock_on_sale_filter.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_with_not_in_stock_on_sale_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_not_in_stock_on_sale_filter.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStockOnSale';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_not_in_stock_min_stock_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_not_instock_min_stock_filter.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['stockFilter'] = 'notInStockMinStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_not_in_stock_min_stock_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_not_instock_min_stock_filter.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_INSTOCK_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['stockFilter'] = 'notInStockMinStock';
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_custom_instock_greater_than_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_custom_instock_greater_than_filter.xml';

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

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_with_custom_instock_greater_than_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_custom_instock_greater_than_filter.csv';

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

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_instock_xml_export_with_custom_instock_lower_than_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_custom_instock_lower_than_filter.xml';

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

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_instock_csv_export_with_custom_instock_lower_than_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_instock_with_custom_instock_lower_than_filter.csv';

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

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_prices_xml_export()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_prices.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_prices_csv_export()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_prices.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_articles_translations_xml_export()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_translations.xml';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_TRANSLATIONS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_articles_translations_csv_export()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_articles_translations.csv';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::ARTICLES_TRANSLATIONS_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        $this->assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_orders_xml_export()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_orders_csv_export()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_orders_xml_export_with_ordernumber_from_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_ordernumber_from_filter.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_orders_csv_export_with_ordernumber_from_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_ordernumber_from_filter.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_orders_xml_export_with_date_from_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_date_from_filter.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_orders_csv_export_with_date_from_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_date_from_filter.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_orders_xml_export_with_date_to_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_date_to_filter.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_orders_csv_export_with_date_to_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_date_to_filter.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_orders_xml_export_with_orderstate_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_orderstate_filter.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_orders_csv_export_with_orderstate_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_orderstate_filter.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_orders_xml_export_with_paymentstate_filter()
    {
        $expectedXmlFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_paymentstate_filter.xml';

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

        $this->assertXmlFileEqualsXmlFile($expectedXmlFile, $file, "Expected both files to be equal, expected {$expectedXmlFile}.");
    }

    public function test_orders_csv_export_with_paymentstate_filter()
    {
        $expectedCsvFile = BackendControllerTestHelper::EXPECTED_EXPORT_FILES_DIR . '/export_orders_with_paymentstate_filter.csv';

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

        $this->assertFileEquals($expectedCsvFile, $file, "Expected both files to be equal, expected {$expectedCsvFile}.");
    }

    public function test_newsletter_csv_export()
    {
        $this->backendControllerTestHelper->createNewsletterDemoData();

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
    }

    public function test_newsletter_xml_export()
    {
        $this->backendControllerTestHelper->createNewsletterDemoData();

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
    }

    /**
     * @return array
     */
    private function getExportRequestParams()
    {
        return [
            'profileId' => '',
            'sessionId' => '',
            'format' => '',
            'limit' => '',
            'offset' => '',
            'categories' => '',
            'variants' => '',
            'ordernumberFrom' => '',
            'dateFrom' => '',
            'dateTo' => '',
            'orderstate' => '',
            'paymentstate' => '',
            'stockFilter' => 'all'
        ];
    }

    /**
     * @param $file
     * @return int
     */
    private function getLineAmount($file)
    {
        return count(file($file));
    }
}
