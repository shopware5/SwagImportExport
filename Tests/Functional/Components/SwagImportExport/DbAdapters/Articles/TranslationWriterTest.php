<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\SwagImportExport\DbAdapters\Articles;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DbAdapters\Articles\TranslationWriter;
use Shopware\Components\SwagImportExport\Exception\AdapterException;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;

class TranslationWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testWriteShouldThrowExceptionIfLanguageIdIsNotAvailable()
    {
        $articleId = 273;
        $variantId = 273;
        $mainDetailId = 273;
        $translations = [
            [
                'languageId' => 3,
            ],
        ];

        $translationWriter = $this->createTranslationWriter();

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('Shop mit ID 3 nicht gefunden');
        $translationWriter->write($articleId, $variantId, $mainDetailId, $translations);
    }

    public function testWriteShouldCreateTranslations()
    {
        $articleId = 273;
        $variantId = 273;
        $mainDetailId = 273;
        $translations = [
            [
                'name' => 'translatedName',
                'description' => 'Translated descritpion',
                'metaTitle' => 'Translated meta title',
                'keywords' => 'translated,keywords',
                'additionalText' => 'Translated additional text',
                'packUnit' => 'translated pack unit',
                'languageId' => '2',
            ],
        ];

        $translationsWriter = $this->createTranslationWriter();
        $translationsWriter->write($articleId, $variantId, $mainDetailId, $translations);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = "SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey=273";
        $result = $connection->executeQuery($sql)->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        static::assertEquals($translations[0]['name'], $importedTranslation['txtArtikel']);
        static::assertEquals($translations[0]['description'], $importedTranslation['txtshortdescription']);
        static::assertEquals($translations[0]['additionalText'], $importedTranslation['txtzusatztxt']);
        static::assertEquals($translations[0]['packUnit'], $importedTranslation['txtpackunit']);
    }

    public function testWriteShouldCreateAttributeTranslations()
    {
        /** @var ModelManager $modelManager */
        $modelManager = Shopware()->Container()->get('models');
        $modelManager->rollback();

        /** @var Connection $dbalConnection */
        $dbalConnection = Shopware()->Container()->get('dbal_connection');
        /** @var CrudService $attributeService */
        $attributeService = Shopware()->Container()->get('shopware_attribute.crud_service');
        $attributeService->update('s_articles_attributes', 'mycustomfield', 'string', ['translatable' => true]);

        $articleId = 272;
        $variantId = 827;
        $mainDetailId = 827;
        $translations = [
            [
                'mycustomfield' => 'my custom translation',
                'languageId' => '2',
            ],
        ];

        $translationsWriter = $this->createTranslationWriter();
        $translationsWriter->write($articleId, $variantId, $mainDetailId, $translations);

        $attributeService->delete('s_articles_attributes', 'mycustomfield');

        $sql = "SELECT * FROM s_core_translations WHERE objecttype='article' AND objectkey=272";
        $result = $dbalConnection->executeQuery($sql)->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        // trait rollback not working - so we rollback manually
        $dbalConnection->executeQuery("DELETE FROM s_core_translations WHERE objecttype='article' AND objectkey=272");
        $dbalConnection->executeQuery('DELETE FROM s_articles_translations WHERE articleID=272');

        static::assertEquals($translations[0]['mycustomfield'], $importedTranslation['__attribute_mycustomfield']);

        $modelManager->beginTransaction();
    }

    public function testWriteShouldCreateVariantTranslation()
    {
        $articleId = 273;
        $variantId = 1053;
        $mainDetailId = 273;
        $translations = [
            [
                'name' => 'translatedName',
                'description' => 'Translated descritpion',
                'metaTitle' => 'Translated meta title',
                'keywords' => 'translated,keywords',
                'additionalText' => 'Translated additional text',
                'packUnit' => 'translated pack unit',
                'languageId' => '2',
            ],
        ];

        $translationWriter = $this->createTranslationWriter();
        $translationWriter->write($articleId, $variantId, $mainDetailId, $translations);

        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $sql = "SELECT * FROM s_core_translations WHERE objecttype='variant' AND objectkey=1053";
        $result = $connection->executeQuery($sql)->fetchAll();
        $importedTranslation = \unserialize($result[0]['objectdata']);

        static::assertEquals('Translated additional text', $importedTranslation['txtzusatztxt']);
        static::assertEquals('translated pack unit', $importedTranslation['txtpackunit']);
    }

    /**
     * @return TranslationWriter
     */
    private function createTranslationWriter()
    {
        return new TranslationWriter();
    }
}
