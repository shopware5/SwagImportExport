<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Providers\DataProvider;
use Symfony\Component\Yaml\Parser;

class DbAdapterTestHelper extends TestCase
{
    use DatabaseTestCaseTrait;
    use ContainerTrait;

    protected string $dbAdapter;

    protected string $dbTable;

    protected ?Parser $parser = null;

    protected string $yamlFile;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    protected ?array $dataProvider = null;

    public function getDataProvider(string $testCase): array
    {
        if ($this->dataProvider === null) {
            $this->dataProvider = $this->parseYaml((string) \file_get_contents($this->getYamlFile($this->yamlFile)));
        }

        return $this->dataProvider[$testCase];
    }

    public function parseYaml(string $yamlFile): array
    {
        if ($this->parser === null) {
            $this->parser = new Parser();
        }

        return $this->parser->parse($yamlFile);
    }

    /**
     * @param array<string> $columns
     * @param array<int>    $ids
     */
    public function read(array $columns, array $ids, array $expectedResults, int $expectedCount, string $section = 'default'): void
    {
        $dataProvider = $this->getContainer()->get(DataProvider::class);
        $rawData = $dataProvider->createDbAdapter($this->dbAdapter)->read($ids, $columns);
        foreach ($expectedResults as $index => $expectedResult) {
            foreach ($expectedResult as $column => $value) {
                static::assertEquals($value, $rawData[$section][$index][$column], "The value of `$column` field does not match!");
            }
        }

        static::assertCount($expectedCount, $rawData[$section]);
    }

    public function readRecordIds(int $start, int $limit, array $filter, array $expectedIds, int $expectedCount): void
    {
        $dataProvider = $this->getContainer()->get(DataProvider::class);
        $ids = $dataProvider->createDbAdapter($this->dbAdapter)->readRecordIds($start, $limit, $filter);

        foreach ($ids as $index => $id) {
            static::assertSame($expectedIds[$index], $id, 'Expected id = ' . $expectedIds[$index] . ' does not match with actual id = ' . $id);
        }

        // no records found check
        if ($ids === []) {
            static::assertEmpty($ids);
            static::assertEmpty($expectedIds, 'There are no actual ids, but we received expected ids.');
        }

        static::assertCount($expectedCount, $ids);
    }

    public function write(array $records, int $expectedInsertedRows): void
    {
        $recordsCountBeforeImport = $this->getTableCount($this->dbTable);

        $dbAdapter = $this->getContainer()->get(DataProvider::class)->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($records);

        $recordsCountAfterImport = $this->getTableCount($this->dbTable);

        static::assertEquals($expectedInsertedRows, $recordsCountAfterImport - $recordsCountBeforeImport);
    }

    protected function getYamlFile(string $fileName): string
    {
        return __DIR__ . '/../Integration/Components/' . $fileName;
    }

    private function getTableCount(string $tableName): int
    {
        return (int) $this->getContainer()->get('dbal_connection')->createQueryBuilder()
            ->select('COUNT(id)')
            ->from($tableName)
            ->execute()
            ->fetchOne();
    }
}
