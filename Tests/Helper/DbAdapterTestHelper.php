<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use SwagImportExport\Components\Factories\DataFactory;
use Symfony\Component\Yaml\Parser;

class DbAdapterTestHelper extends ImportExportTestHelper
{
    use ContainerTrait;

    protected string $dbAdapter;

    protected string $dbTable;

    protected ?Parser $parser = null;

    protected string $yamlFile;

    /**
     * @var array<string, array<string, mixed>>|null
     */
    protected ?array $dataProvider = null;

    /**
     * @return array
     */
    public function getDataProvider(string $testCase)
    {
        if ($this->dataProvider === null) {
            $this->dataProvider = $this->parseYaml(
                \file_get_contents(
                    $this->getYamlFile($this->yamlFile)
                )
            );
        }

        return $this->dataProvider[$testCase];
    }

    /**
     * @return array
     */
    public function parseYaml(string $yamlFile)
    {
        if ($this->parser === null) {
            $this->parser = new Parser();
        }

        return $this->parser->parse($yamlFile);
    }

    /**
     * @param int[] $ids
     */
    public function read(array $columns, array $ids, array $expectedResults, int $expectedCount, string $section = 'default')
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $rawData = $dbAdapter->read($ids, $columns);
        foreach ($expectedResults as $index => $expectedResult) {
            foreach ($expectedResult as $column => $value) {
                $this->assertEquals($value, $rawData[$section][$index][$column], "The value of `$column` field does not match!");
            }
        }

        $this->assertCount($expectedCount, $rawData[$section]);
    }

    public function readRecordIds(int $start, int $limit, array $filter, array $expectedIds, int $expectedCount)
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $ids = $dbAdapter->readRecordIds($start, $limit, $filter);

        foreach ($ids as $index => $id) {
            $this->assertSame($expectedIds[$index], $id, 'Expected id = ' . $expectedIds[$index] . ' does not match with actual id = ' . $id);
        }

        // no records found check
        if ($ids === []) {
            $this->assertEmpty($ids);
            $this->assertEmpty($expectedIds, 'There are no actual ids, but we received expected ids.');
        }

        $this->assertCount($expectedCount, $ids);
    }

    public function write(array $records, int $expectedInsertedRows)
    {
        $recordsCountBeforeImport = $this->getTableCount($this->dbTable);

        $dataFactory = $this->getContainer()->get(DataFactory::class);

        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($records);

        $recordsCountAfterImport = $this->getTableCount($this->dbTable);

        $this->assertEquals($expectedInsertedRows, $recordsCountAfterImport - $recordsCountBeforeImport);
    }

    /**
     * @return string
     */
    protected function getYamlFile(string $fileName)
    {
        return __DIR__ . '/../Integration/Components/' . $fileName;
    }

    private function getTableCount(string $tableName): int
    {
        return (int) $this->getContainer()->get('dbal_connection')->createQueryBuilder()
            ->select('COUNT(id)')
            ->from($tableName)
            ->execute()
            ->fetch(\PDO::FETCH_COLUMN);
    }
}
