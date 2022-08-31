<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Controllers\Backend;

use Doctrine\DBAL\Connection;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\DataProvider\ProfileDataProvider;
use SwagImportExport\Tests\Helper\ExportControllerTrait;
use SwagImportExport\Tests\Helper\FixturesImportTrait;
use SwagImportExport\Tests\Helper\ReflectionHelperTrait;

class ProductExportTest extends \Enlight_Components_Test_Controller_TestCase
{
    use ExportControllerTrait;
    use FixturesImportTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;
    use ReflectionHelperTrait;

    public const FORMAT_XML = 'xml';
    public const FORMAT_CSV = 'csv';

    public const CATEGORY_ID_VINTAGE = 31;
    public const PRODUCT_STREAM_ID = 999999;

    public function setUp(): void
    {
        parent::setUp();

        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    public function testProductXmlExport(): void
    {
        $params = $this->getExportRequestParams();

        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $this->assertProductAttributeInXml($file, 'SW10012', 'name', 'Kobra Vodka 37,5%');
        $this->assertProductAttributeInXml($file, 'SW10012', 'instock', '60');
        $this->assertProductAttributeInXml($file, 'SW10012', 'supplier', 'Feinbrennerei Sasse');

        $this->assertProductAttributeInXml($file, 'SW10009', 'name', 'Special Finish Lagerkorn X.O. 32%');
        $this->assertProductAttributeInXml($file, 'SW10009', 'instock', '12');
        $this->assertProductAttributeInXml($file, 'SW10009', 'supplier', 'Feinbrennerei Sasse');
    }

    public function testProductCsvExport(): void
    {
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, sprintf('File not found %s', $fileName));
        $this->backendControllerTestHelper->addFile($file);

        $exportedProducts = $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
        static::assertEquals('Kobra Vodka 37,5%', $exportedProducts['SW10012']['name']);
        static::assertEquals('60', $exportedProducts['SW10012']['instock']);
        static::assertEquals('Feinbrennerei Sasse', $exportedProducts['SW10012']['supplier']);

        static::assertEquals('Special Finish Lagerkorn X.O. 32%', $exportedProducts['SW10009']['name']);
        static::assertEquals('12', $exportedProducts['SW10009']['instock']);
        static::assertEquals('Feinbrennerei Sasse', $exportedProducts['SW10009']['supplier']);
    }

    public function testProductXmlExportWithLimit(): void
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $productList = $this->queryXpath($file, '//article');
        static::assertEquals($limit, $productList->length);
    }

    public function testProductCsvExportWithLimit(): void
    {
        $limit = 10;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['limit'] = $limit;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount($limit, $mappedProductList);
    }

    public function testProductXmlExportWithOffset210(): void
    {
        $offset = 210;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['offset'] = $offset;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $productListWithOffset = $this->queryXpath($file, '//article');
        static::assertEquals(15, $productListWithOffset->length);
    }

    public function testProductCsvExportWithOffset210(): void
    {
        $offset = 210;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['offset'] = $offset;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount(15, $mappedProductList);
    }

    public function testProductXmlExportWithCategoryFilter(): void
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        // Fetch all article nodes with given category
        $productDomNodeList = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $productDomNodeList->length);
    }

    public function testProductXmlExportWithCategoryFilterWithBatchSize(): void
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $connection = $this->getConnection();
        $connection->executeStatement('Update s_core_config_elements set value = "i:1;" where name = "batch-size-export"');

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_XML;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        // Fetch all article nodes with given category
        $productDomNodeList = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $productDomNodeList->length);
    }

    public function testProductCsvExportWithCategoryFilter(): void
    {
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['categories'] = $categoryId;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount(10, $mappedProductList);
    }

    public function testProductXmlExportWithProductStreamFilter(): void
    {
        $productStreamId = self::PRODUCT_STREAM_ID;

        $this->addProductStream();
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    public function testProductCsvExportWithProductStreamFilter(): void
    {
        $productStreamId = self::PRODUCT_STREAM_ID;

        $this->addProductStream();
        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    public function testVariantXmlExport(): void
    {
        $exportVariants = 'true';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    public function testVariantCsvExport(): void
    {
        $exportVariants = 'true';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
        $params['format'] = self::FORMAT_CSV;
        $params['variants'] = $exportVariants;
        $this->Request()->setParams($params);

        $this->dispatch('backend/SwagImportExportExport/export');
        $assigned = $this->View()->getAssign('data');

        $fileName = $assigned['fileName'];
        $file = $this->uploadPathProvider->getRealPath($fileName);

        static::assertFileExists($file, "File not found {$fileName}");
        $this->backendControllerTestHelper->addFile($file);

        $mappedProductList = $this->readCsvIndexedByOrdernumber($file);
        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedProductList['SW10002.3']['name']);
        static::assertEquals('0,5 Liter', $mappedProductList['SW10002.3']['additionalText']);

        static::assertEquals('Münsterländer Lagerkorn 32%', $mappedProductList['SW10002.1']['name']);
        static::assertEquals('1,5 Liter', $mappedProductList['SW10002.1']['additionalText']);
    }

    public function testVariantXmlExportWithLimit(): void
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

    public function testVariantCsvExportWithLimit(): void
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

        $mappedProductList = $this->readCsvIndexedByOrdernumber($file);
        static::assertCount($limit, $mappedProductList);
    }

    public function testVariantXmlExportWithOffset(): void
    {
        $variants = 'true';
        $offset = '380';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    public function testVariantCsvExportWithOffset(): void
    {
        $exportVariants = 'true';
        $offset = '380';

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    public function testVariantXmlExportWithCategoryFilter(): void
    {
        $exportVariants = 'true';
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

        // Fetch all article nodes with given category
        $variantNodeListWithCategoryFilter = $this->queryXpath($file, "//article/category[categories='{$categoryId}']");
        static::assertEquals(10, $variantNodeListWithCategoryFilter->length);
    }

    public function testVariantCsvExportWithCategoryFilter(): void
    {
        $exportVariants = 'true';
        $categoryId = self::CATEGORY_ID_VINTAGE;

        $params = $this->getExportRequestParams();
        $params['profileId'] = $this->backendControllerTestHelper->getProfileIdByType(ProfileDataProvider::PRODUCT_PROFILE_TYPE);
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

    private function assertProductAttributeInXml(string $filePath, string $orderNumber, string $attribute, string $expected): void
    {
        $productDomNodeList = $this->queryXpath($filePath, "//article[ordernumber='{$orderNumber}']/{$attribute}");
        $node = $productDomNodeList->item(0);
        static::assertInstanceOf(\DOMNode::class, $node);
        $nodeValue = $node->nodeValue;
        static::assertEquals($expected, $nodeValue);
    }

    private function readCsvIndexedByOrdernumber(string $file): array
    {
        return $this->csvToArrayIndexedByFieldValue($file, 'ordernumber');
    }

    private function getConnection(): Connection
    {
        return $this->getContainer()->get('dbal_connection');
    }
}
