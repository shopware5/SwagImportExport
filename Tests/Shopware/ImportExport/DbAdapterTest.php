<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class DbAdapterTest extends ImportExportTestHelper
{

    protected $dbAdaptor;
    protected $dbTable;
    protected static $parser;
    protected static $yamlFile;
    protected static $dataProvider;

    public static function assertTablesEqual(PHPUnit_Extensions_Database_DataSet_ITable $expected, PHPUnit_Extensions_Database_DataSet_ITable $actual, $message = '')
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

    public function read($columns, $ids, $expected, $expectedCount)
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdaptor);

        $rawData = $dbAdapter->read($ids, $columns);

        $rawData = $rawData['default'];

        foreach ($expected as $key1 => $value) {
            foreach ($value as $key2 => $val) {
                $this->assertEquals($rawData[$key1][$key2], $val);
            }
        }
        $this->assertEquals(count($rawData), $expectedCount);
    }

    public function readRecordIds($start, $limit, $expectedCount)
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdaptor);

        $ids = $catDbAdapter->readRecordIds($start, $limit);
        $this->assertEquals($expectedCount, count($ids));
    }

    public function defaultColumns()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdaptor);

        $columns = $catDbAdapter->getDefaultColumns();

        $this->assertTrue(is_array($columns));
    }

    public function write($data, $expectedInsertedRows)
    {
        $beforeTestCount = $this->getDatabaseTester()->getConnection()->getRowCount($this->dbTable);

        $dataFactory = $this->Plugin()->getDataFactory();

        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdaptor);
        $catDbAdapter->write($data);

        $afterTestCount = $this->getDatabaseTester()->getConnection()->getRowCount($this->dbTable);

        $this->assertEquals($expectedInsertedRows, $afterTestCount - $beforeTestCount);
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

        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdaptor);
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

        $catDbAdapter = $dataFactory->createDbAdapter($this->dbAdaptor);
        $catDbAdapter->write($category);

        // Assert
        $queryTable = $this->getDatabaseTester()->getConnection()->createQueryTable(
                $this->dbTable, 'SELECT ' . $columnsSelect . ' FROM ' . $this->dbTable
        );

        $this->assertTablesEqual($expectedTable, $queryTable);
    }

}
