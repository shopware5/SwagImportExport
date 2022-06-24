<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Tests\Helper\DbAdapterTestHelper;

class ProductsPricesDbAdapterTest extends DbAdapterTestHelper
{
    protected string $yamlFile = 'TestCases/articlePricesDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();
        $this->dbAdapter = 'articlesPrices';
        $this->dbTable = 's_articles_prices';
    }

    /**
     * @param int[] $ids
     *
     * @dataProvider readProvider
     */
    public function testRead(array $columns, array $ids, array $expected, int $expectedCount): void
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider(): array
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds(int $start, int $limit, array $expected, int $expectedCount): void
    {
        $this->readRecordIds($start, $limit, [], $expected, $expectedCount);
    }

    public function readRecordIdsProvider(): array
    {
        return $this->getDataProvider('testReadRecordIds');
    }
}
