<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\DbAdapters\Products;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\DbAdapters\Products\ConfiguratorWriter;
use SwagImportExport\Components\DbAdapters\Results\ProductWriterResult;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\DatabaseTestCaseTrait;
use SwagImportExport\Tests\Helper\ReflectionHelperTrait;

class ConfiguratorWriterTest extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;
    use ReflectionHelperTrait;

    public function testUpdateConfigurationSetTypeAndCreationOfOption(): void
    {
        $connection = $this->getContainer()->get('dbal_connection');
        $sql = \file_get_contents(__DIR__ . '/_fixtures/configurator.sql');
        static::assertIsString($sql);
        $connection->executeQuery($sql);

        $configuratorWriter = $this->getConfiguratorWriter();

        $cacheUpdate = $this->getReflectionMethod(ConfiguratorWriter::class, 'getSets')->invoke($configuratorWriter);
        $this->getReflectionProperty(ConfiguratorWriter::class, 'sets')->setValue($configuratorWriter, $cacheUpdate);

        $productWriterResult = new ProductWriterResult(2, 3, 3);
        $configuratorData = [
            [
                'configSetType' => 0,
                'configSetId' => 100,
                'configGroupId' => 100,
                'configOptionName' => 'foo Liter',
            ],
        ];
        $configuratorWriter->writeOrUpdateConfiguratorSet($productWriterResult, $configuratorData);

        $updatedSet = $this->getContainer()->get('dbal_connection')
            ->executeQuery('SELECT * FROM s_article_configurator_sets WHERE id=?', [100])->fetchAllAssociative();
        static::assertEquals(0, (int) $updatedSet[0]['type']);

        $count = $this->getContainer()->get('dbal_connection')
            ->executeQuery('SELECT COUNT(*) FROM s_article_configurator_set_group_relations WHERE set_id=? AND group_id=?', [100, 100])->fetchOne();
        static::assertEquals(1, (int) $count);

        $count = $this->getContainer()->get('dbal_connection')
            ->executeQuery('SELECT COUNT(*) FROM s_article_configurator_options WHERE name=?', ['foo Liter'])->fetchOne();
        static::assertEquals(1, (int) $count);
    }

    private function getConfiguratorWriter(): ConfiguratorWriter
    {
        return $this->getContainer()->get(ConfiguratorWriter::class);
    }
}
