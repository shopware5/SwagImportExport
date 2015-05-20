<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\DbAdapterTest;

class ArticlesPricesDbAdapterTest extends DbAdapterTest
{

    protected static $yamlFile = "TestCases/articlePricesDbAdaptor.yml";

    public function setUp()
    {
        parent::setUp();

        $this->dbAdaptor = 'articlesPrices';
        $this->dbTable = 's_articles_prices';
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
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider()
    {
        return static::getDataProvider('testRead');
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
        return static::getDataProvider('testReadRecordIds');
    }
}
