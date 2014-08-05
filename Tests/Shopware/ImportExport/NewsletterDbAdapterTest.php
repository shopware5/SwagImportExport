<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\DbAdapterTest;

class NewsletterDbAdapterTest extends DbAdapterTest
{
    protected static $yamlFile = "TestCases/newslettersDbAdaptor.yml";
    
    public function setUp()
    {
        parent::setUp();
        
        $this->dbAdaptor = 'newsletter';
        $this->dbTable = 's_campaigns_mailaddresses';
    }

    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
                dirname(__FILE__) . "/Database/newsletters.yml"
        );
    }

    /**
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected, $expectedCount)
    {
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider()
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expectedCount)
    {
        $this->readRecordIds($start, $limit, $expectedCount);
    }

    public function readRecordIdsProvider()
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    public function testDefaultColumns()
    {
        $this->defaultColumns();
    }

    /**
     * @dataProvider writeProvider
     */
    public function testWrite($data, $expectedInsertedRows)
    {
        $this->write($data, $expectedInsertedRows);
    }

    public function writeProvider()
    {
        return $this->getDataProvider('testWrite');
    }

    /**
     * @dataProvider insertOneProvider
     */
    public function testInsertOne($category, $expectedRow)
    {
        $this->insertOne($category, $expectedRow);
    }

    public function insertOneProvider()
    {
        return $this->getDataProvider('testInsertOne');
    }

    /**
     * @dataProvider updateOneProvider
     */
    public function testUpdateOne($category, $expectedRow)
    {
        $this->updateOne($category, $expectedRow);
    }

    public function updateOneProvider()
    {
        return $this->getDataProvider('testUpdateOne');
    }

}
