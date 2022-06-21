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

class ArticleAccessoriesProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testShouldWriteAssertNewArticleAsseccory(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_accessories_profile.csv';
        $expectedOrderNumber = 'SW10003';
        $expectedArticleAccessoryId = 10;

        $this->runCommand("sw:import:import -p default_article_accessories {$filePath}");

        $updatedArticle = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedOrderNumber}'");
        $updatedArticleRelations = $this->executeQuery("SELECT * FROM s_articles_relationships WHERE articleID='{$updatedArticle[0]['articleID']}'");

        static::assertEquals($expectedArticleAccessoryId, $updatedArticleRelations[0]['relatedarticle']);
    }
}
