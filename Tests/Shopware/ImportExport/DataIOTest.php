<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Shopware\ImportExport;

use Shopware\Components\SwagImportExport\Factories\DataFactory;
use Shopware\Components\SwagImportExport\Logger\Logger;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ImportExportTestHelper;

class DataIOTest extends ImportExportTestHelper
{
    use ContainerTrait;

    /**
     * @return array
     */
    public function getPostData()
    {
        return [
            'adapter' => 'categories',
            'filter' => '',
            'type' => 'export',
            'limit' => ['limit' => 40, 'offset' => 0],
            'max_record_count' => 100,
            'format' => 'csv',
            'profileId' => 1,
        ];
    }

    public function testPreloadRecordIds()
    {
        $postData = $this->getPostData();
        $postData['limit'] = [];

        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $dataSession = $dataFactory->loadSession($postData);

        $dbAdapter = $dataFactory->createDbAdapter($postData['adapter']);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $dataIO->initialize([], $limit, $filter, 'import', 'csv', $postData['max_record_count']);

        $dataIO->preloadRecordIds();

        $allIds = $dataIO->getRecordIds();

        static::assertCount(62, $allIds);
    }

    public function testGenerateDirectory()
    {
        $postData = $this->getPostData();

        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $dbAdapter = $dataFactory->createDbAdapter($postData['adapter']);
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());

        $directory = $dataIO->getDirectory();
        static::assertDirectoryExists($directory);
    }

    /**
     * @return Logger
     */
    private function getLogger()
    {
        return $this->getContainer()->get(Logger::class);
    }
}
