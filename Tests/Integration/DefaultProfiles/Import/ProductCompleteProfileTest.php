<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ProductCompleteProfileTest extends TestCase
{
    use DefaultProfileImportTestCaseTrait;
    use CommandTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    protected function setUp(): void
    {
        $csvFile = __DIR__ . '/_fixtures/article_complete.csv';
        $fixtureImagePath = 'file://' . \ImportExportTestKernel::IMPORT_FILES_DIR . 'sw-icon_blue128.png';
        $csvContentWithExternalImagePath = \str_replace('[placeholder_for_fixture_image]', $fixtureImagePath, (string) \file_get_contents($csvFile));
        \file_put_contents($csvFile, $csvContentWithExternalImagePath);
    }

    protected function tearDown(): void
    {
        $csvFile = __DIR__ . '/_fixtures/article_complete.csv';
        $fixtureImagePath = 'file://' . \ImportExportTestKernel::IMPORT_FILES_DIR . 'sw-icon_blue128.png';
        $csvContentWithPlaceholder = \str_replace($fixtureImagePath, '[placeholder_for_fixture_image]', (string) \file_get_contents($csvFile));
        \file_put_contents($csvFile, $csvContentWithPlaceholder);
    }

    public function testImportShouldCreateProductWithVariants(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='Article with Variants'");
        static::assertEquals('Article with Variants', $importedProduct[0]['name']);
        static::assertEquals(1, $importedProduct[0]['active']);

        $importedVariants = $this->executeQuery("SELECT * FROM s_articles_details WHERE articleID={$importedProduct[0]['id']} ORDER BY ordernumber");
        static::assertCount(6, $importedVariants, 'Import did not import expected 6 variants');
        static::assertEquals(0, $importedVariants[1]['purchasePrice']);
        static::assertEquals(999, $importedVariants[1]['instock']);
        static::assertEquals('with different instock', $importedVariants[1]['additionaltext']);

        $importedProductImages = $this->executeQuery("SELECT * FROM s_articles_img WHERE articleID={$importedProduct[0]['id']}");
        static::assertCount(6, $importedProductImages, 'Import did not import expected 6 images');
        foreach ($importedProductImages as $importedProductImage) {
            static::assertStringContainsString('sw-icon_blue128', $importedProductImage['img']);
        }
    }

    public function testImportShouldCreateVariantWithDifferentPricesForCustomerGroups(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedVariantWithDifferentPrice = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='test-10001.2'");

        $importedVariantPrice = $this->executeQuery("SELECT * FROm s_articles_prices WHERE articledetailsID={$importedVariantWithDifferentPrice[0]['id']} ORDER BY pricegroup");
        static::assertEquals(839.49579831933, $importedVariantPrice[0]['price'], 'Could not import gross price for customer group EK as net price.');
        static::assertEquals(550, $importedVariantPrice[1]['price'], 'Could not import price for customer group H');
    }

    public function testImportShouldCreateProductWithDifferentPricesForCustomerGroups(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedMainVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='test-10001.1'");

        $importedProductPrice = $this->executeQuery("SELECT * FROM s_articles_prices WHERE articledetailsID={$importedMainVariant[0]['id']} ORDER BY pricegroup");
        static::assertEquals(84.033613445378, $importedProductPrice[0]['price'], 'Could not import gross price for customer group EK as net price.');
        static::assertEquals(150, $importedProductPrice[1]['price'], 'Could not import price for customer group H');
    }

    public function testImportShouldImportProductWithAttributes(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='Article with Variants'");
        $importedAttributes = $this->executeQuery("SELECT * FROM s_articles_attributes WHERE articledetailsID={$importedProduct[0]['main_detail_id']}");
        static::assertEquals('attribute 1', $importedAttributes[0]['attr1']);
        static::assertEquals('attribute 2', $importedAttributes[0]['attr2']);
        static::assertEquals('comment', $importedAttributes[0]['attr3']);
    }

    public function testImportShouldImportVariantWithAttributes(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='test-10001.1'");
        $importedAttributes = $this->executeQuery("SELECT * FROM s_articles_attributes WHERE articledetailsID={$importedVariant[0]['id']}");
        static::assertEquals('attribute1', $importedAttributes[0]['attr1']);
        static::assertEquals('attribute2', $importedAttributes[0]['attr2']);
        static::assertEquals('comment', $importedAttributes[0]['attr3']);
    }

    public function testImportShouldImportTranslations(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedVariant = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='test-10001.1'");
        $result = $this->executeQuery("SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey='{$importedVariant[0]['articleID']}'");
        $importedTranslation = \unserialize($result[0]['objectdata']);

        static::assertEquals('Translated Name', $importedTranslation['txtArtikel']);
        static::assertEquals('short description translation', $importedTranslation['txtshortdescription']);
        static::assertEquals('meta title description', $importedTranslation['metaTitle']);
    }

    public function testImportShouldCreateSimilarAssociations(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='Article with Variants'");
        $similarRelation = $this->executeQuery("SELECT * FROM s_articles_similar WHERE articleID={$importedProduct[0]['id']}");
        $createdSimilarProduct = $this->executeQuery("SELECT * FROM s_articles WHERE id={$similarRelation[0]['relatedarticle']}");

        static::assertEquals('Similar article', $createdSimilarProduct[0]['name']);
    }

    public function testImportShouldCreateAccessoryAssociations(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='Article with Variants'");
        $similarRelation = $this->executeQuery("SELECT * FROM s_articles_relationships WHERE articleID={$importedProduct[0]['id']}");
        $createdSimilarProduct = $this->executeQuery("SELECT * FROM s_articles WHERE id={$similarRelation[0]['relatedarticle']}");

        static::assertEquals('Accessory article', $createdSimilarProduct[0]['name']);
    }

    public function testImportShouldCreateMediaFromExternalRessource(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_complete.csv';
        $this->runCommand(sprintf('sw:import:import -p default_articles_complete %s', $filePath));

        $importedProduct = $this->executeQuery("SELECT * FROM s_articles WHERE name='Article with Variants'");
        $mediaRelation = $this->executeQuery("SELECT * FROM s_articles_img WHERE articleID='{$importedProduct[0]['id']}'");
        $mediaFromExternalResource = $this->executeQuery("SELECT * FROM s_media WHERE id='{$mediaRelation[0]['media_id']}'");

        static::assertStringStartsWith('media/image/sw-icon_blue', $mediaFromExternalResource[0]['path']);
    }
}
