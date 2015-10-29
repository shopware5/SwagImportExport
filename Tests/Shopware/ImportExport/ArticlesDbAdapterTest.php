<?php

namespace Tests\Shopware\ImportExport;

class ArticlesDbAdapterTest extends DbAdapterTest
{
    protected static $yamlFile = "TestCases/articleDbAdapter.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdapter = 'articles';
        $this->dbTable = 's_articles';
    }

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . "/Database/articles.yml"
        );
    }

    /**
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected, $expectedCount)
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);

        $rawData = $dbAdapter->read($ids, $columns);

        foreach ($expected as $key1 => $elements) {
            foreach ($elements as $key2 => $element) {
                foreach ($element as $key3 => $val) {
                    $this->assertEquals($rawData[$key1][$key2][$key3], $val);
                }
            }
        }

        $this->assertEquals(count($rawData['article']), $expectedCount);
    }

    public function readProvider()
    {
        return static::getDataProvider('testRead');
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite($data, $expectedInsertedRows)
    {
        $tableNames = array_keys($expectedInsertedRows);

        $expectedTables = array();
        $insertedTables = array();

        foreach ($tableNames as $tableName) {
            $expectedTables[$tableName] = $this->getExpectedData($tableName, $expectedInsertedRows);
        }

        // Start the action
        $dataFactory = $this->Plugin()->getDataFactory();

        $dbAdapter = $dataFactory->createDbAdapter($this->dbAdapter);
        $dbAdapter->write($data);

        foreach ($tableNames as $tableName) {
            $insertedTable = $this->getInsertedDataTable($expectedTables[$tableName]['columns'], $tableName);
            $this->assertTablesEqual($expectedTables[$tableName]['table'], $insertedTable);
        }
    }

    public function writeProvider()
    {
        return static::getDataProvider('testWrite');
    }

    private function getExpectedData($table, $expectedInsertedRows)
    {
        $articlesRows = $expectedInsertedRows[$table];

        $columnsSelect = implode(', ', array_keys($articlesRows[0]));

        $articleTableBefore = $this->getDatabaseTester()->getConnection()->createQueryTable(
            $table, 'SELECT ' . $columnsSelect . ' FROM ' . $table . ' ORDER BY id'
        );

        $expectedArticleTable = new \PHPUnit_Extensions_Database_DataSet_DefaultTable($articleTableBefore->getTableMetaData());

        $expectedArticleTable->addTableRows($articleTableBefore);

        foreach ($articlesRows as $articlesRow) {
            $expectedArticleTable->addRow($articlesRow);
        }

        return array('columns' => $columnsSelect, 'table' => $expectedArticleTable);
    }

    private function getInsertedDataTable($columns, $table)
    {
        return $this->getDatabaseTester()->getConnection()->createQueryTable(
            $table, 'SELECT ' . $columns . ' FROM ' . $table . ' ORDER BY id'
        );
    }
}
