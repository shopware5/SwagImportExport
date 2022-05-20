<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Tests\Helper\DbAdapterTestHelper;

class NewsletterDbAdapterTest extends DbAdapterTestHelper
{
    protected string $yamlFile = 'TestCases/newslettersDbAdapter.yml';

    public function setUp(): void
    {
        parent::setUp();

        $this->dbAdapter = 'newsletter';
        $this->dbTable = 's_campaigns_mailaddresses';
    }

    /**
     * @param array $data
     * @param int   $expectedInsertedRows
     *
     * @dataProvider writeProvider
     */
    public function testWrite($data, $expectedInsertedRows)
    {
        $this->write($data, $expectedInsertedRows);
    }

    /**
     * @return array
     */
    public function writeProvider()
    {
        return $this->getDataProvider('testWrite');
    }
}
