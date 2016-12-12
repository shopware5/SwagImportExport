<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\DefaultProfiles\Import;

use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ArticleSimilarsProfileTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;

    public function test_write_should_assert_new_similar_article()
    {
        $filePath = __DIR__ . '/_fixtures/article_similars_profile.csv';
        $expectedOrderNumber = "SW10003";
        $expectedRelatedArticleId = 7;

        $this->runCommand("sw:import:import -p default_similar_articles {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedOrderNumber}'");
        $updatedArticleSimilars = $this->executeQuery("SELECT * FROM s_articles_similar WHERE articleID='{$updatedArticle[0]["articleID"]}'");

        $this->assertEquals($expectedRelatedArticleId, $updatedArticleSimilars[4]["relatedarticle"]);
    }
}