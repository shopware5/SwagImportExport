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

class ArticlePropertiesProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteShouldUpdateExistingProperty(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $createdArticleOrderNumber = 'SW10003';
        $expectedUpdatedValue = '>30%';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticleFilters = $this->executeQuery("SELECT * FROM s_filter_articles WHERE articleID='{$updatedArticle[0]['articleID']}'");
        $updatedArticleFilterValue = $this->executeQuery("SELECT * FROM s_filter_values WHERE id='{$updatedArticleFilters[5]['valueID']}'");

        static::assertEquals($expectedUpdatedValue, $updatedArticleFilterValue[0]['value']);
    }

    public function testWriteShouldCreateNewPropertyValue(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $createdArticleOrderNumber = 'SW10004';
        $expectedInsertedValue = '>90%';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticleFilters = $this->executeQuery("SELECT * FROM s_filter_articles WHERE articleID='{$updatedArticle[0]['articleID']}'");
        $updatedArticleFilterValue = $this->executeQuery("SELECT * FROM s_filter_values WHERE id='{$updatedArticleFilters[5]['valueID']}'");

        static::assertEquals($expectedInsertedValue, $updatedArticleFilterValue[0]['value']);
    }

    public function testWriteShouldCreateNewPropertyGroup(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $expectedInsertedGroupName = 'New property group';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $filterOptions = $this->executeQuery('SELECT * FROM s_filter ORDER BY id DESC LIMIT 1');

        static::assertEquals($expectedInsertedGroupName, $filterOptions[0]['name']);
    }

    public function testWriteShouldCreateNewPropertyOption(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $expectedInsertedGroupName = 'New value name';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $filterOptions = $this->executeQuery('SELECT * FROM s_filter_options ORDER BY id DESC LIMIT 1');

        static::assertEquals($expectedInsertedGroupName, $filterOptions[0]['name']);
    }
}
