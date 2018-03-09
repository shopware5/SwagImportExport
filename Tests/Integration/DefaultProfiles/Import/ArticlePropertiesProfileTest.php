<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ArticlePropertiesProfileTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_write_should_update_existing_property()
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $createdArticleOrderNumber = 'SW10003';
        $expectedUpdatedValue = '>30%';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticleFilters = $this->executeQuery("SELECT * FROM s_filter_articles WHERE articleID='{$updatedArticle[0]['articleID']}'");
        $updatedArticleFilterValue = $this->executeQuery("SELECT * FROM s_filter_values WHERE id='{$updatedArticleFilters[5]['valueID']}'");

        $this->assertEquals($expectedUpdatedValue, $updatedArticleFilterValue[0]['value']);
    }

    public function test_write_should_create_new_property_value()
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $createdArticleOrderNumber = 'SW10004';
        $expectedInsertedValue = '>90%';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$createdArticleOrderNumber}'");
        $updatedArticleFilters = $this->executeQuery("SELECT * FROM s_filter_articles WHERE articleID='{$updatedArticle[0]['articleID']}'");
        $updatedArticleFilterValue = $this->executeQuery("SELECT * FROM s_filter_values WHERE id='{$updatedArticleFilters[5]['valueID']}'");

        $this->assertEquals($expectedInsertedValue, $updatedArticleFilterValue[0]['value']);
    }

    public function test_write_should_create_new_property_group()
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $expectedInsertedGroupName = 'New property group';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $filterOptions = $this->executeQuery('SELECT * FROM s_filter ORDER BY id DESC LIMIT 1');

        $this->assertEquals($expectedInsertedGroupName, $filterOptions[0]['name']);
    }

    public function test_write_should_create_new_property_option()
    {
        $filePath = __DIR__ . '/_fixtures/article_properties_profile.csv';
        $expectedInsertedGroupName = 'New value name';

        $this->runCommand("sw:import:import -p default_article_properties {$filePath}");

        $filterOptions = $this->executeQuery('SELECT * FROM s_filter_options ORDER BY id DESC LIMIT 1');

        $this->assertEquals($expectedInsertedGroupName, $filterOptions[0]['name']);
    }
}
