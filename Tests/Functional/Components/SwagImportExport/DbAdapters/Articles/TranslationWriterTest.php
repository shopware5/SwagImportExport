<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\TranslationWriter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class TranslationWriterTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTestCaseTrait;

    public function testWriteShouldThrowExceptionIfLanguageIdIsNotAvailable(): void
    {
        $productId = 273;
        $variantId = 273;
        $mainDetailId = 273;
        $translations = [
            [
                'languageId' => 3,
            ],
        ];

        $translationWriter = $this->getTranslationWriter();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Shop mit ID 3 nicht gefunden');
        $translationWriter->write($productId, $variantId, $mainDetailId, $translations);
    }

    public function testWriteShouldCreateTranslations(): void
    {
        $productId = 273;
        $variantId = 273;
        $mainDetailId = 273;
        $translations = [
            [
                'name' => 'translatedName',
                'description' => 'Translated description',
                'metaTitle' => 'Translated meta title',
                'keywords' => 'translated,keywords',
                'additionalText' => 'Translated additional text',
                'packUnit' => 'translated pack unit',
                'shippingTime' => 'Translated shipping time',
                'languageId' => '2',
            ],
        ];

        $translationsWriter = $this->getTranslationWriter();
        $translationsWriter->write($productId, $variantId, $mainDetailId, $translations);

        $connection = $this->getContainer()->get('dbal_connection');
        $sql = "SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey=273";
        $result = $connection->executeQuery($sql)->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        static::assertSame($translations[0]['name'], $importedTranslation['txtArtikel']);
        static::assertSame($translations[0]['description'], $importedTranslation['txtshortdescription']);
        static::assertSame($translations[0]['metaTitle'], $importedTranslation['metaTitle']);
        static::assertSame($translations[0]['keywords'], $importedTranslation['txtkeywords']);
        static::assertSame($translations[0]['additionalText'], $importedTranslation['txtzusatztxt']);
        static::assertSame($translations[0]['packUnit'], $importedTranslation['txtpackunit']);
        static::assertSame($translations[0]['shippingTime'], $importedTranslation['txtshippingtime']);
    }

    public function testWriteShouldCreateAttributeTranslations(): void
    {
        $modelManager = $this->getContainer()->get('models');
        $modelManager->rollback();

        $dbalConnection = $this->getContainer()->get('dbal_connection');
        $attributeService = $this->getContainer()->get('shopware_attribute.crud_service');
        $attributeService->update('s_articles_attributes', 'mycustomfield', 'string', ['translatable' => true]);

        $productId = 272;
        $variantId = 827;
        $mainDetailId = 827;
        $translations = [
            [
                'mycustomfield' => 'my custom translation',
                'languageId' => '2',
            ],
        ];

        $translationsWriter = $this->getTranslationWriter();
        $translationsWriter->write($productId, $variantId, $mainDetailId, $translations);

        $attributeService->delete('s_articles_attributes', 'mycustomfield');

        $sql = "SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey=272";
        $result = $dbalConnection->executeQuery($sql)->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        // trait rollback not working - so we roll back manually
        $dbalConnection->executeQuery("DELETE FROM s_core_translations WHERE objecttype='article' AND objectkey=272");
        $dbalConnection->executeQuery('DELETE FROM s_articles_translations WHERE articleID=272');

        static::assertSame($translations[0]['mycustomfield'], $importedTranslation['__attribute_mycustomfield']);

        $modelManager->beginTransaction();
    }

    public function testWriteShouldCreateVariantTranslation(): void
    {
        $productId = 273;
        $variantId = 1053;
        $mainDetailId = 273;
        $translations = [
            [
                'name' => 'translatedName',
                'description' => 'Translated description',
                'metaTitle' => 'Translated meta title',
                'keywords' => 'translated,keywords',
                'additionalText' => 'Translated additional text',
                'packUnit' => 'translated pack unit',
                'shippingTime' => 'Translated shipping time',
                'languageId' => '2',
            ],
        ];

        $translationWriter = $this->getTranslationWriter();
        $translationWriter->write($productId, $variantId, $mainDetailId, $translations);

        $connection = $this->getContainer()->get('dbal_connection');
        $sql = "SELECT * FROM s_core_translations WHERE objecttype='variant' AND objectkey=1053";
        $result = $connection->executeQuery($sql)->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        static::assertSame($translations[0]['additionalText'], $importedTranslation['txtzusatztxt']);
        static::assertSame($translations[0]['packUnit'], $importedTranslation['txtpackunit']);
        static::assertSame($translations[0]['shippingTime'], $importedTranslation['txtshippingtime']);
    }

    private function getTranslationWriter(): TranslationWriter
    {
        return Shopware()->Container()->get(TranslationWriter::class);
    }
}
