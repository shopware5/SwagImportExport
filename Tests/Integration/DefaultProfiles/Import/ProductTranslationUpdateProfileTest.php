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
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ProductTranslationUpdateProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testImportShouldUpdateExistingProductTranslation(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_translation_profile_update.csv';
        $expectedProductName = 'Munsterland Aperitif 16% Super deluxe';
        $expectedProductDescription = 'Test description';
        $expectedProductLongDescription = 'Test description long';

        $this->runCommand("sw:import:import -p default_article_translations_update {$filePath}");

        $queryResult = $this->executeQuery(
            "SELECT * FROM s_core_translations as t JOIN s_articles_details as a ON t.objectkey = a.id AND t.objecttype = 'article' WHERE a.ordernumber = 'SW10003'"
        );

        $product = $queryResult[0];
        $translations = \unserialize($product['objectdata']);

        static::assertEquals($expectedProductName, $translations['txtArtikel']);
        static::assertEquals($expectedProductDescription, $translations['txtshortdescription']);
        static::assertEquals($expectedProductLongDescription, $translations['txtlangbeschreibung']);
    }
}
