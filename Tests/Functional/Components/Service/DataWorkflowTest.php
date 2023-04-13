<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Tests\Functional\Traits\DatabaseTransactionBehaviour;
use SwagImportExport\Components\DbAdapters\ProductsPricesDbAdapter;
use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Components\Providers\FileIOProvider;
use SwagImportExport\Components\Service\DataWorkflow;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Components\Structs\ImportRequest;
use SwagImportExport\Models\Profile as ProfileModel;
use SwagImportExport\Tests\Functional\Components\Service\Mock\FileIoMock;
use SwagImportExport\Tests\Functional\Components\Service\Mock\TransformerChainMock;
use SwagImportExport\Tests\Helper\ContainerTrait;

class DataWorkflowTest extends TestCase
{
    use ContainerTrait;
    use DatabaseTransactionBehaviour;

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function testImportDoesNotOverwritePrices(): void
    {
        $tree = __DIR__ . '/../_fixtures/tree.json';
        $file = __DIR__ . '/../_fixtures/emptyFile.csv';
        $customerGroupInsert = __DIR__ . '/../_fixtures/customerGroupInsert.sql';

        static::assertFileExists($tree);
        static::assertFileExists($file);
        static::assertFileExists($customerGroupInsert);

        $conn = $this->getConnection();

        $conn->executeQuery((string) file_get_contents($customerGroupInsert));

        $profileModel = new ProfileModel();
        $profileModel->setType('articlesPrices');
        $profileModel->setTree((string) file_get_contents($tree));
        $profileModel->setName('default_article_prices');
        $profileEntity = new Profile($profileModel);

        $importRequest = new ImportRequest();
        $dataSet = [
            ['profileEntity' => $profileEntity],
            ['format' => 'csv'],
            ['inputFile' => $file],
        ];

        foreach ($dataSet as $data) {
            $importRequest->setData($data);
        }

        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $session->setTotalCount(4);
        $session->method('getState')->willReturn('active');

        $workflow = $this->getWorkflow();

        $workflow->import($importRequest, $session);

        $prices = $conn->createQueryBuilder()->select('price')->from('s_articles_prices')->where('articleID = 272')->execute();
        $prices = array_merge(...$prices->fetchAllNumeric());

        static::assertIsArray($prices);
        static::assertCount(5, $prices);
        static::assertEquals(['183.64485981308', '159.15887850467', '153.03738317757', '146.91588785047', '140.79439252336'], $prices);
    }

    public function testSaveUnprocessedDataShouldWriteHeader(): void
    {
        $file = __DIR__ . '/../_fixtures/emptyFile.csv';
        $handle = \fopen($file, 'r+');
        static::assertIsResource($handle);
        \ftruncate($handle, 0);
        \fclose($handle);
        $workflow = $this->getWorkflow();
        $workflow->saveUnprocessedData([], 'someProfileName', $file, 'old');

        $expected = 'new | empty | header | test' . \PHP_EOL . 'just | another | return | value';
        $result = \file_get_contents($file);

        static::assertSame($expected, $result);
    }

    private function getWorkflow(): DataWorkflow
    {
        $dbAdapter = $this->getContainer()->get(ProductsPricesDbAdapter::class);
        $dataProvider = $this->getMockBuilder(DataProvider::class)->disableOriginalConstructor()->getMock();
        $dataProvider->method('createDbAdapter')->willReturn($dbAdapter);
        $dataTransformationFactory = $this->getMockBuilder(DataTransformerFactory::class)->disableOriginalConstructor()->getMock();
        $dataTransformationFactory->method('createDataTransformerChain')->willReturn(new TransformerChainMock());
        $fileIOProvider = $this->getMockBuilder(FileIOProvider::class)->disableOriginalConstructor()->getMock();
        $fileIOProvider->method('getFileWriter')->willReturn(new FileIoMock());
        $fileIOProvider->method('getFileReader')->willReturn(new FileIoMock());
        $sessionService = $this->getMockBuilder(SessionService::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $profileFactory = $this->getMockBuilder(ProfileFactory::class)->disableOriginalConstructor()->getMock();
        $profileFactory->method('loadHiddenProfile')->willReturn(new Profile(new ProfileModel()));

        return new DataWorkflow(
            $dataProvider,
            $dataTransformationFactory,
            $fileIOProvider,
            $sessionService,
            $logger,
            $profileFactory
        );
    }

    /**
     * @returns Connection
     */
    private function getConnection(): Connection
    {
        return $this->getContainer()->get(Connection::class);
    }
}
