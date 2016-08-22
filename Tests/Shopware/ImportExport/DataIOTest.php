<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Shopware\Components\SwagImportExport\Logger\Logger;

class DataIOTest extends ImportExportTestHelper
{
    public function getPostData()
    {
        return array(
            'adapter' => 'categories',
            'filter' => '',
            'type' => 'export',
            'limit' => array('limit' => 40, 'offset' => 0),
            'max_record_count' => 100,
            'format' => 'csv',
            'profileId' => 1,
        );
    }

    public function testPreloadRecordIds()
    {
        $postData = $this->getPostData();
        $postData['limit'] = array();

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($postData, $dataSession, $this->getLogger());

        $dataIO->preloadRecordIds();

        $allIds = $dataIO->getRecordIds();

        $this->assertEquals(count($allIds), 62);
    }

    public function testGenerateDirectorty()
    {
        $postData = $this->getPostData();

        $dataFactory = $this->Plugin()->getDataFactory();
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($postData, $dataSession, $this->getLogger());

        $directory = $dataIO->getDirectory();

        $expectedCategory = '/var/www/files/import_export/';

        $this->assertEquals($directory, $expectedCategory);
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return Shopware()->Container()->get('swag_import_export.logger');
    }
}
