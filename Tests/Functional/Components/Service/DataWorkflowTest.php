<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Functional\Components\Service;

use PHPUnit\Framework\TestCase;
use SwagImportExport\Components\Factories\DataTransformerFactory;
use SwagImportExport\Components\Factories\ProfileFactory;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Providers\DataProvider;
use SwagImportExport\Components\Providers\FileIOProvider;
use SwagImportExport\Components\Service\DataWorkflow;
use SwagImportExport\Components\Session\SessionService;
use SwagImportExport\Models\Profile as ProfileModel;
use SwagImportExport\Tests\Functional\Components\Service\Mock\FileIoMock;
use SwagImportExport\Tests\Functional\Components\Service\Mock\TransformerChainMock;

class DataWorkflowTest extends TestCase
{
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

    public function getWorkflow(): DataWorkflow
    {
        $dataProvider = $this->getMockBuilder(DataProvider::class)->disableOriginalConstructor()->getMock();
        $dataTransformationFactory = $this->getMockBuilder(DataTransformerFactory::class)->disableOriginalConstructor()->getMock();
        $dataTransformationFactory->method('createDataTransformerChain')->willReturn(new TransformerChainMock());
        $fileIOProvider = $this->getMockBuilder(FileIOProvider::class)->disableOriginalConstructor()->getMock();
        $fileIOProvider->method('getFileWriter')->willReturn(new FileIoMock());
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
}
