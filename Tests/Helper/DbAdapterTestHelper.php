<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Symfony\Component\Yaml\Parser;

class DbAdapterTestHelper extends ImportExportTestHelper
{
    use ContainerTrait;

    /**
     * @var string
     */
    protected $dbAdapter;

    /**
     * @var string
     */
    protected $dbTable;

    /**
     * @var Parser
     */
    protected $parser;

    protected $yamlFile;

    protected $dataProvider;

    /**
     * @param string $testCase
     *
     * @return array
     */
    public function getDataProvider($testCase)
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
     * @param string $yamlFile
     *
     * @return array
     */
    public function parseYaml($yamlFile)
    {
        if ($this->parser === null) {
            $this->parser = new Parser();
        }

        return $this->parser->parse($yamlFile);
    }

    /**
     * @param array  $columns
     * @param int[]  $ids
     * @param array  $expectedResults
     * @param int    $expectedCount
     * @param string $section
     */
    public function read($columns, $ids, $expectedResults, $expectedCount, $section = 'default')
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

    /**
     * @param int   $start
     * @param int   $limit
     * @param array $filter
     * @param array $expectedIds
     * @param int   $expectedCount
     */
    public function readRecordIds($start, $limit, $filter, $expectedIds, $expectedCount)
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

    /**
     * @param array $records
     * @param int   $expectedInsertedRows
     */
    public function write($records, $expectedInsertedRows)
    {
        $recordsCountBeforeImport = $this->getTableCount($this->dbTable);

        $dataFactory = $this->getContainer()->get(DataFactory::class);

        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($records);

        $recordsCountAfterImport = $this->getTableCount($this->dbTable);

        $this->assertEquals($expectedInsertedRows, $recordsCountAfterImport - $recordsCountBeforeImport);
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getYamlFile($fileName)
    {
        return __DIR__ . '/../Shopware/ImportExport/' . $fileName;
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
