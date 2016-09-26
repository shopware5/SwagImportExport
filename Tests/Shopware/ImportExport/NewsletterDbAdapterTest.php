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
}
