<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\DependencyInjection\Container;
use SwagImportExport\Components\UploadPathProvider;

trait ExportControllerTrait
{
    private BackendControllerTestHelper $backendControllerTestHelper;

    private UploadPathProvider $uploadPathProvider;

    abstract public function getContainer(): Container;

    /**
     * @before
     */
    protected function loadDependenciesBefore(): void
    {
        $this->backendControllerTestHelper = new BackendControllerTestHelper();

        $this->uploadPathProvider = $this->getContainer()->get(UploadPathProvider::class);
    }

    /**
     * @after
     */
    protected function unlinkFiles(): void
    {
        $this->backendControllerTestHelper->tearDown();
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function queryXpath(string $filePath, string $xpath): \DOMNodeList
    {
        $domDocument = new \DOMDocument();
        $xml = \file_get_contents($filePath);
        static::assertIsString($xml);
        $domDocument->loadXML($xml);

        $path = (new \DOMXPath($domDocument))->query($xpath);

        self::assertInstanceOf(\DOMNodeList::class, $path);

        return $path;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function csvToArrayIndexedByFieldValue(string $filePath, string $indexField): array
    {
        $csv = \fopen($filePath, 'rb');
        static::assertIsResource($csv);
        $mappedCsv = [];

        $header = \fgetcsv($csv, 0, ';');
        static::assertIsArray($header);
        while (($row = \fgetcsv($csv, 0, ';')) !== false) {
            static::assertIsArray($row);
            $tmpRow = \array_combine($header, $row);
            static::assertIsArray($tmpRow);
            $mappedCsv[$tmpRow[$indexField]] = $tmpRow;
        }

        return $mappedCsv;
    }

    /**
     * @return array<string, string>
     */
    private function getExportRequestParams(): array
    {
        return [
            'profileId' => '',
            'sessionId' => '',
            'format' => '',
            'limit' => '',
            'offset' => '',
            'categories' => '',
            'productStreamId' => '',
            'variants' => '',
            'ordernumberFrom' => '',
            'dateFrom' => '',
            'dateTo' => '',
            'orderstate' => '',
            'paymentstate' => '',
            'stockFilter' => 'all',
        ];
    }
}
