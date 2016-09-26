<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Helper;

use Doctrine\DBAL\Connection;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter;
use \Shopware\Components\SwagImportExport\Factories\DataFactory;
use Tests\Helper\ImportExportTestHelper;

class DbAdapterTest extends ImportExportTestHelper
{
    /* @var string $dbAdapter */
    protected $dbAdapter;

    /* @var string $dbTable */
    protected $dbTable;

    /**
     * @var \PHPUnit_Extensions_Database_DataSet_SymfonyYamlParser $parser
     */
    protected $parser;
    protected $yamlFile;
    protected $dataProvider;

    /**
     * @param \PHPUnit_Extensions_Database_DataSet_DefaultTable $expected
     * @param \PHPUnit_Extensions_Database_DataSet_ITable $actual
     * @param string $message
     */
    public function assertTablesEqual(
        \PHPUnit_Extensions_Database_DataSet_DefaultTable $expected,
        \PHPUnit_Extensions_Database_DataSet_ITable $actual,
        $message = ''
    ) {
        $constraint = new \PHPUnit_Extensions_Database_Constraint_TableIsEqual($expected);
        $this->assertThat($actual, $constraint, $message);
    }

    public function getDataProvider($testCase)
    {
        if ($this->dataProvider == null) {
            $this->dataProvider = $this->parseYaml($this->getYamlFile($this->yamlFile));
        }

        return $this->dataProvider[$testCase];
    }

    /**
     * @param string $yamlFile
     * @return array
     */
    public function parseYaml($yamlFile)
    {
        if ($this->parser == null) {
            $this->parser = new \PHPUnit_Extensions_Database_DataSet_SymfonyYamlParser();
        }
        return $this->parser->parseYaml($yamlFile);
    }

    /**
     * @param array $columns
     * @param int[] $ids
     * @param array $expectedResults
     * @param int $expectedCount
     * @param string $section
     */
    public function read($columns, $ids, $expectedResults, $expectedCount,  $section = 'default')
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $rawData = $dbAdapter->read($ids, $columns);
        foreach ($expectedResults as $index => $expectedResult) {
            foreach ($expectedResult as $column => $value) {
                $this->assertEquals($value, $rawData[$section][$index][$column], "The value of `$column` field does not match!");
            }
        }

        $this->assertEquals($expectedCount, count($rawData[$section]));
    }

    /**
     * @param int $start
     * @param array $limit
     * @param array $filter
     * @param array $expectedIds
     * @param int $expectedCount
     */
    public function readRecordIds($start, $limit, $filter, $expectedIds, $expectedCount)
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $ids = $dbAdapter->readRecordIds($start, $limit, $filter);

        foreach ($ids as $index => $id) {
            $this->assertSame($expectedIds[$index], $id, 'Expected id = ' . $expectedIds[$index] . ' does not match with actual id = ' . $id);
        }

        // no records found check
        if ($ids === array()) {
            $this->assertEmpty($ids);
            $this->assertEmpty($expectedIds, 'There are no actual ids, but we received expected ids.');
        }

        $this->assertEquals($expectedCount, count($ids));
    }

    /**
     * @param array $records
     * @param int $expectedInsertedRows
     */
    public function write($records, $expectedInsertedRows)
    {
        $recordsCountBeforeImport = $this->getDatabaseTester()->getConnection()->getRowCount($this->dbTable);
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($records);

        $recordsCountAfterImport = $this->getDatabaseTester()->getConnection()->getRowCount($this->dbTable);

        $this->assertEquals($expectedInsertedRows, $recordsCountAfterImport - $recordsCountBeforeImport);
    }

    /**
     * @param array $data
     * @param array $expectedRow
     */
    public function insertOne($data, $expectedRow)
    {
        // Prepare expected data
        $columnsSelect = implode(', ', array_keys($expectedRow));
        $queryTableBefore = $this->getDatabaseTester()->getConnection()->createQueryTable(
            $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );

        $expectedTable = new \PHPUnit_Extensions_Database_DataSet_DefaultTable($queryTableBefore->getTableMetaData());
        $expectedTable->addTableRows($queryTableBefore);
        $expectedTable->addRow($expectedRow);

        // Start the action
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($data);

        // Assert
        $resultTable = $this->getDatabaseTester()->getConnection()->createQueryTable(
            $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    /**
     * @param array $category
     * @param array $expectedRow
     */
    public function updateOne($category, $expectedRow)
    {
        // Prepare expected data
        $columnsSelect = implode(', ', array_keys($expectedRow));
        $queryTableBefore = $this->getDatabaseTester()->getConnection()->createQueryTable(
                $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );
        $rowCount = $queryTableBefore->getRowCount();
        $expectedTable = new \PHPUnit_Extensions_Database_DataSet_DefaultTable($queryTableBefore->getTableMetaData());

        for ($row = 0; $row < $rowCount; $row++) {
            $currentRow = $queryTableBefore->getRow($row);
            if ($currentRow['id'] == $expectedRow['id']) {
                $expectedTable->addRow($expectedRow);
            } else {
                $expectedTable->addRow($currentRow);
            }
        }

        // Start the action
        $dataFactory = $this->Plugin()->getDataFactory();

        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $catDbAdapter->write($category);

        // Assert
        $queryTable = $this->getDatabaseTester()->getConnection()->createQueryTable(
            $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    /**
     * @param string $fileName
     * @return string
     */
    protected function getYamlFile($fileName)
    {
        return dirname(__FILE__) . '/../Shopware/ImportExport/' . $fileName;
    }
}
