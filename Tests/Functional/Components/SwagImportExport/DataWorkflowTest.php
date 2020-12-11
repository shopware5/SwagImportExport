<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Functional\Components\SwagImportExport;

use PHPUnit\Framework\TestCase;
use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DataWorkflow;
use Shopware\Components\SwagImportExport\Transformers\DataTransformerChain;

class DataWorkflowTest extends TestCase
{
    public function test_saveUnprocessedData_shouldWriteHeader(): void
    {
        $postData['session']['prevState'] = 'old';
        $file = __DIR__ . '/_fixtures/emptyFile.csv';
        $handle = \fopen($file, 'r+');
        \ftruncate($handle, 0);
        \fclose($handle);
        $workflow = $this->getWorkflow();
        $workflow->saveUnprocessedData($postData, 'someProfileName', $file);

        $expected = 'new | empty | header | test' . \PHP_EOL . 'just | another | return | value';
        $result = \file_get_contents($file);

        static::assertSame($expected, $result);
    }

    public function getWorkflow(): DataWorkflowMock
    {
        $dataWorkFlow = new DataWorkflowMock();

        $reflectionDataIO = (new \ReflectionClass(DataWorkflow::class))->getProperty('fileIO');
        $reflectionDataIO->setAccessible(true);
        $reflectionDataIO->setValue($dataWorkFlow, new FileIoMock());

        $reflectionDataIO = (new \ReflectionClass(DataWorkflow::class))->getProperty('transformerChain');
        $reflectionDataIO->setAccessible(true);
        $reflectionDataIO->setValue($dataWorkFlow, new TransformerChainMock());

        return $dataWorkFlow;
    }
}

class DataWorkflowMock extends DataWorkflow
{
    public function __construct()
    {
        // DO NOTHING
    }
}

class FileIoMock extends DataIO
{
    public function __construct()
    {
        // DO NOTHING
    }

    public function writeHeader($outputFile, $data): void
    {
        \file_put_contents($outputFile, $data);
    }

    public function writeRecords($outputFile, $data): void
    {
        \file_put_contents($outputFile, $data, \FILE_APPEND);
    }
}

class TransformerChainMock extends DataTransformerChain
{
    public function __construct()
    {
        // DO NOTHING
    }

    public function composeHeader(): string
    {
        return 'new | empty | header | test';
    }

    public function transformForward($data): string
    {
        return \PHP_EOL . 'just | another | return | value';
    }
}
