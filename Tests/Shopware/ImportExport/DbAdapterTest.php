<?php

namespace Tests\Shopware\ImportExport;

use \Shopware\Components\SwagImportExport\Factories\DataFactory;

class DbAdapterTest extends ImportExportTestHelper
{
    /* @var string $dbAdapter */
    protected $dbAdapter;

    /* @var string $dbTable */
    protected $dbTable;

    /* @var \PHPUnit_Extensions_Database_DataSet_SymfonyYamlParser $parser */
    protected static $parser;
    protected static $yamlFile;
    protected static $dataProvider;

    public static function assertTablesEqual(\PHPUnit_Extensions_Database_DataSet_DefaultTable $expected, PHPUnit_Extensions_Database_DataSet_ITable $actual, $message = '')
    {
        $constraint = new \PHPUnit_Extensions_Database_Constraint_TableIsEqual($expected);

        self::assertThat($actual, $constraint, $message);
    }

    public static function getDataProvider($testCase)
    {
        if (static::$dataProvider == NULL) {
            static::$dataProvider = static::parseYaml(dirname(__FILE__) . '/' . static::$yamlFile);
        }

        return static::$dataProvider[$testCase];
    }

    public static function parseYaml($yamlFile)
    {
        if (static::$parser == NULL) {
            static::$parser = new \PHPUnit_Extensions_Database_DataSet_SymfonyYamlParser();
        }
        return static::$parser->parseYaml($yamlFile);
    }

    public function read($columns, $ids, $expectedResults, $expectedCount)
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $rawData = $dbAdapter->read($ids, $columns);
        $rawData = $rawData['default'];

        foreach ($expectedResults as $index => $expectedResult) {
            foreach ($expectedResult as $column => $value) {
                $this->assertSame($rawData[$index][$column], $value, "The value of `$column` field does not match!");
            }
        }
        $this->assertEquals(count($rawData), $expectedCount);
    }

    public function readRecordIds($start, $limit, $expectedIds, $expectedCount)
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $ids = $dbAdapter->readRecordIds($start, $limit);

        $this->assertEquals($expectedIds, $ids);
        $this->assertEquals($expectedCount, count($ids));
    }

    public function defaultColumns($expectedColumns, $expectedCount)
    {
        /* @var DataFactory $dataFactory */
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $columns = $dbAdapter->getDefaultColumns();

        $this->assertTrue(is_array($columns));
        $this->assertEquals($expectedColumns, $columns);
        $this->assertEquals($expectedCount, count($columns));
    }

    public function write($records, $expectedInsertedRows)
    {
        $recordsCountBeforeImport = $this->getDatabaseTester()->getConnection()->getRowCount($this->dbTable);

        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($records);

        $recordsCountAfterImport = $this->getDatabaseTester()->getConnection()->getRowCount($this->dbTable);

        $this->assertEquals($expectedInsertedRows, $recordsCountAfterImport - $recordsCountBeforeImport);
    }

    public function insertOne($category, $expectedRow)
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

        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $catDbAdapter->write($category);

        // Assert
        $queryTable = $this->getDatabaseTester()->getConnection()->createQueryTable(
                $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

    public function updateOne($category, $expectedRow)
    {
        // Prepare expected data
        $columnsSelect = implode(', ', array_keys($expectedRow));
        $queryTableBefore = $this->getDatabaseTester()->getConnection()->createQueryTable(
                $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );
        $rowCount = $queryTableBefore->getRowCount();
        $expectedTable = new \PHPUnit_Extensions_Database_DataSet_DefaultTable($queryTableBefore->getTableMetaData());

        for ($i = 0; $i < $rowCount; $i++) {
            $row = $queryTableBefore->getRow($i);
            if ($row['id'] == $expectedRow['id']) {
                $expectedTable->addRow($expectedRow);
            } else {
                $expectedTable->addRow($row);
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

}
