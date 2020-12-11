<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\TestsIntegration\DefaultProfiles\Import;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class TranslationProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;

    const LANGUAGE_ENGLISH_ID = 2;

    const BRANDY_PROPERTYGROUP_INDEX = 0;
    const CHOCOLATE_BROWN_PROPERTYGROUP_INDEX = 14;
    const AMOUNT_OF_IMPORTED_CONFIGURATOR_TRANSLATIONS = 3;
    const CONFIGURATOR_15LITER_INDEX = 0;

    public function test_import_translations_should_create_new_translations_for_properties()
    {
        $this->truncateTranslationTable();

        $filePath = __DIR__ . '/_fixtures/translations_properties_create.csv';

        $this->runCommand("sw:importexport:import -p default_system_translations {$filePath}");

        $importedPropertyTranslations = $this->executeQuery('SELECT * FROM s_core_translations ORDER BY objectKey');

        static::assertEquals('propertygroup', $importedPropertyTranslations[self::BRANDY_PROPERTYGROUP_INDEX]['objecttype']);
        static::assertEquals('a:1:{s:9:"groupName";s:6:"Brandy";}', $importedPropertyTranslations[self::BRANDY_PROPERTYGROUP_INDEX]['objectdata']);
        static::assertEquals(self::LANGUAGE_ENGLISH_ID, $importedPropertyTranslations[self::BRANDY_PROPERTYGROUP_INDEX]['objectlanguage']);

        static::assertEquals('propertyvalue', $importedPropertyTranslations[self::CHOCOLATE_BROWN_PROPERTYGROUP_INDEX]['objecttype']);
        static::assertEquals('a:1:{s:11:"optionValue";s:15:"chocolate brown";}', $importedPropertyTranslations[self::CHOCOLATE_BROWN_PROPERTYGROUP_INDEX]['objectdata']);
    }

    public function test_import_translations_should_update_translations_for_properties()
    {
        $this->truncateTranslationTable();

        $filePath = __DIR__ . '/_fixtures/translations_properties_update.csv';

        $this->runCommand("sw:importexport:import -p default_system_translations {$filePath}");

        $updatedPropertyTranslations = $this->executeQuery('SELECT * FROM s_core_translations ORDER BY objectKey');

        static::assertEquals('propertygroup', $updatedPropertyTranslations[self::BRANDY_PROPERTYGROUP_INDEX]['objecttype']);
        static::assertEquals('a:1:{s:9:"groupName";s:13:"Brandy UPDATE";}', $updatedPropertyTranslations[self::BRANDY_PROPERTYGROUP_INDEX]['objectdata']);

        static::assertEquals('propertyvalue', $updatedPropertyTranslations[self::CHOCOLATE_BROWN_PROPERTYGROUP_INDEX]['objecttype']);
        static::assertEquals('a:1:{s:11:"optionValue";s:22:"chocolate brown UPDATE";}', $updatedPropertyTranslations[self::CHOCOLATE_BROWN_PROPERTYGROUP_INDEX]['objectdata']);
    }

    public function test_import_translations_should_create_translations_for_configurators()
    {
        $this->truncateTranslationTable();
        $filePath = __DIR__ . '/_fixtures/translations_configurators_create.csv';

        $this->runCommand("sw:importexport:import -p default_system_translations {$filePath}");

        $importedConfiguratorTranslations = $this->executeQuery('SELECT * FROM s_core_translations ORDER BY objectKey');

        static::assertEquals('configuratoroption', $importedConfiguratorTranslations[self::CONFIGURATOR_15LITER_INDEX]['objecttype']);
        static::assertEquals('a:1:{s:4:"name";s:21:"1,5 liter TRANSLATION";}', $importedConfiguratorTranslations[self::CONFIGURATOR_15LITER_INDEX]['objectdata']);
        static::assertEquals(self::LANGUAGE_ENGLISH_ID, $importedConfiguratorTranslations[self::CONFIGURATOR_15LITER_INDEX]['objectlanguage']);
        static::assertCount(self::AMOUNT_OF_IMPORTED_CONFIGURATOR_TRANSLATIONS, $importedConfiguratorTranslations);
    }

    public function test_import_translations_should_update_configurator_translations()
    {
        $this->truncateTranslationTable();
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->executeQuery(\file_get_contents(__DIR__ . '/_fixtures/configurator_translations_demo.sql'));

        $filePath = __DIR__ . '/_fixtures/translations_configurators_update.csv';

        $this->runCommand("sw:importexport:import -p default_system_translations {$filePath}");

        $updatedConfiguratorTranslations = $this->executeQuery('SELECT * FROM s_core_translations ORDER BY objectKey');

        static::assertEquals('a:1:{s:4:"name";s:16:"1,5 liter UPDATE";}', $updatedConfiguratorTranslations[self::CONFIGURATOR_15LITER_INDEX]['objectdata']);
        static::assertEquals('configuratoroption', $updatedConfiguratorTranslations[self::CONFIGURATOR_15LITER_INDEX]['objecttype']);
        static::assertEquals(self::LANGUAGE_ENGLISH_ID, $updatedConfiguratorTranslations[self::CONFIGURATOR_15LITER_INDEX]['objectlanguage']);
    }

    private function truncateTranslationTable()
    {
        /** @var Connection $connection */
        $connection = Shopware()->Container()->get('dbal_connection');
        $connection->executeQuery('DELETE FROM s_core_translations WHERE id > 0');
    }
}
