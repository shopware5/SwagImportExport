<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Integration\Components;

use SwagImportExport\Components\Factories\DataFactory;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Utils\DataColumnOptions;
use SwagImportExport\Tests\Helper\ContainerTrait;
use SwagImportExport\Tests\Helper\ImportExportTestHelper;

class DataIOTest extends ImportExportTestHelper
{
    use ContainerTrait;

    public function getPostData(): array
    {
        return [
            'adapter' => 'categories',
            'filter' => [],
            'type' => 'export',
            'limit' => ['limit' => 40, 'offset' => 0],
            'max_record_count' => 100,
            'format' => 'csv',
            'profileId' => 1,
        ];
    }

    public function testPreloadRecordIds(): void
    {
        $postData = $this->getPostData();
        $postData['limit'] = [];

        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $dataSession = $dataFactory->loadSession($postData);

        $dbAdapter = $dataFactory->createDbAdapter($postData['adapter']);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());
        $limit = $dataFactory->createLimit($postData['limit']);
        $filter = $dataFactory->createFilter($postData['filter']);
        $dataIO->initialize(new DataColumnOptions(''), $limit, $filter, 'import', 'csv', $postData['max_record_count']);

        $dataIO->preloadRecordIds();

        $allIds = $dataIO->getRecordIds();

        static::assertCount(62, $allIds);
    }

    public function testGenerateDirectory(): void
    {
        $postData = $this->getPostData();

        $dataFactory = $this->getContainer()->get(DataFactory::class);
        $dbAdapter = $dataFactory->createDbAdapter($postData['adapter']);
        $dataSession = $dataFactory->loadSession($postData);

        $dataIO = $dataFactory->createDataIO($dbAdapter, $dataSession, $this->getLogger());

        $directory = $dataIO->getDirectory();
        static::assertDirectoryExists($directory);
    }

    private function getLogger(): Logger
    {
        return $this->getContainer()->get(Logger::class);
    }
}
