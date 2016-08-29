<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Shopware\Components\SwagImportExport\Logger\Logger;
use Tests\Helper\ImportExportTestHelper;

class DataIOTest extends ImportExportTestHelper
{
    /**
     * @return array
     */
    public function getPostData()
    {
        return [
            'adapter' => 'categories',
            'filter' => '',
            'type' => 'export',
            'limit' => [ 'limit' => 40, 'offset' => 0 ],
            'max_record_count' => 100,
            'format' => 'csv',
            'profileId' => 1,
        ];
    }

    public function testPreloadRecordIds()
    {
        $postData = $this->getPostData();
        $postData['limit'] = [];

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataSession = $dataFactory->loadSession($postData);

        $dbAdapter = $dataFactory->createDbAdapter($postData['adapter']);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $dataIO->initialize([], $limit, $filter, 'import', 'csv', $postData['max_record_count']);

        $dataIO->preloadRecordIds();

        $allIds = $dataIO->getRecordIds();

        $this->assertEquals(62, count($allIds));
    }

    public function testGenerateDirectory()
    {
        $postData = $this->getPostData();

        $dataFactory = $this->Plugin()->getDataFactory();
        $dbAdapter = $dataFactory->createDbAdapter($postData['adapter']);
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());

        $directory = $dataIO->getDirectory();
        $this->assertTrue(is_dir($directory));
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return Shopware()->Container()->get('swag_import_export.logger');
    }
}
