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
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ArticleSimilarsProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testWriteShouldAssertNewSimilarArticle(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_similars_profile.csv';
        $expectedOrderNumber = 'SW10003';
        $expectedRelatedArticleId = [
            0 => 2,
            1 => 4,
            2 => 6,
        ];

        $this->runCommand("sw:import:import -p default_similar_articles {$filePath}");

        $updatedArticleId = $this->executeQuery("SELECT articleID FROM s_articles_details WHERE ordernumber='{$expectedOrderNumber}'", \PDO::FETCH_COLUMN)[0];
        $updatedArticleSimilars = $this->executeQuery("SELECT * FROM s_articles_similar WHERE articleID='{$updatedArticleId}'");

        foreach (\array_keys($expectedRelatedArticleId) as $key) {
            static::assertEquals($expectedRelatedArticleId[$key], $updatedArticleSimilars[$key]['relatedarticle']);
        }

        // Now deleted element
        static::assertNull($updatedArticleSimilars[3]);
    }
}
