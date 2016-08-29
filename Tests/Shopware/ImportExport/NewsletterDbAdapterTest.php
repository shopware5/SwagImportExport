<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Tests\Helper\DbAdapterTest;

class NewsletterDbAdapterTest extends DbAdapterTest
{
    protected $yamlFile = "TestCases/newslettersDbAdapter.yml";
    
    public function setUp()
    {
        parent::setUp();
        
        $this->dbAdapter = 'newsletter';
        $this->dbTable = 's_campaigns_mailaddresses';
    }

    /**
     * @param array $data
     * @param int $expectedInsertedRows
     *
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
     * @param array $columns
     * @param int[] $ids
     * @param array $expected
     * @param int $expectedCount
     *
     * @depends testWrite
     * @dataProvider readProvider
     */
    public function testRead($columns, $ids, $expected, $expectedCount)
    {
        $this->markTestIncomplete('Incomplete');
        $this->read($columns, $ids, $expected, $expectedCount);
    }

    public function readProvider()
    {
        return $this->getDataProvider('testRead');
    }

    /**
     * @param int $start
     * @param array $limit
     * @param int $expectedCount
     *
     * @depends testWrite
     * @dataProvider readRecordIdsProvider
     */
    public function testReadRecordIds($start, $limit, $expectedCount)
    {
        $this->markTestIncomplete('Incomplete');
        $this->readRecordIds($start, $limit, [], [], $expectedCount);
    }

    public function readRecordIdsProvider()
    {
        return $this->getDataProvider('testReadRecordIds');
    }

    /**
     * @param array $category
     * @param array $expectedRow
     *
     * @depends testWrite
     * @dataProvider updateOneProvider
     */
    public function testUpdateOne($category, $expectedRow)
    {
        $this->markTestIncomplete('Incomplete');
        $this->updateOne($category, $expectedRow);
    }

    public function updateOneProvider()
    {
        return $this->getDataProvider('testUpdateOne');
    }
}
