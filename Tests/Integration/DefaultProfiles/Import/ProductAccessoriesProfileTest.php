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

class ProductAccessoriesProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    public function testShouldWriteAssertNewProductAccessory(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_accessories_profile.csv';
        $expectedOrderNumber = 'SW10003';
        $expectedProductAccessoryId = 10;

        $this->runCommand("sw:import:import -p default_article_accessories {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedOrderNumber}'");
        $updatedProductRelations = $this->executeQuery("SELECT * FROM s_articles_relationships WHERE articleID='{$updatedProduct[0]['articleID']}'");

        static::assertEquals($expectedProductAccessoryId, $updatedProductRelations[0]['relatedarticle']);
    }
}
