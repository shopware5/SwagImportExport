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
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Tests\Helper\CommandTestCaseTrait;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Integration\DefaultProfiles\DefaultProfileImportTestCaseTrait;

class ProductInStockProfileTest extends TestCase
{
    use CommandTestCaseTrait;
    use DefaultProfileImportTestCaseTrait;
    use DatabaseTransactionBehaviour;
    use ContainerTrait;

    public function testWriteShouldUpdateProductStock(): void
    {
        $filePath = __DIR__ . '/_fixtures/article_in_stock_profile.csv';
        $expectedProductOrderNumber = 'SW10003';
        $expectedProductStock = 47;

        $this->runCommand("sw:import:import -p default_article_in_stock {$filePath}");

        $updatedProduct = $this->executeQuery("SELECT * FROM s_articles_details WHERE ordernumber='{$expectedProductOrderNumber}'");

        static::assertEquals($expectedProductStock, $updatedProduct[0]['instock']);
    }
}
