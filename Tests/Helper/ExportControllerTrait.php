<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Tests\Helper;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\SwagImportExport\UploadPathProvider;

trait ExportControllerTrait
{
    /**
     * @var BackendControllerTestHelper
     */
    private $backendControllerTestHelper;

    /**
     * @var UploadPathProvider
     */
    private $uploadPathProvider;

    abstract public function getContainer(): Container;

    /**
     * @before
     */
    protected function loadDependenciesBefore()
    {
        $this->backendControllerTestHelper = new BackendControllerTestHelper();

        $this->uploadPathProvider = $this->getContainer()->get('swag_import_export.upload_path_provider');
    }

    /**
     * @param string $filePath
     * @param string $xpath
     *
     * @return \DOMNodeList
     */
    private function queryXpath($filePath, $xpath)
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML(\file_get_contents($filePath));

        $domXpath = new \DOMXPath($domDocument);

        return $domXpath->query($xpath);
    }

    /**
     * @param string $filePath
     * @param string $indexField
     *
     * @return array
     */
    private function csvToArrayIndexedByFieldValue($filePath, $indexField)
    {
        $csv = \fopen($filePath, 'rb');
        $mappedCsv = [];

        $header = \fgetcsv($csv, 0, ';');
        while (($row = \fgetcsv($csv, 0, ';')) !== false) {
            $tmpRow = \array_combine($header, $row);
            $mappedCsv[$tmpRow[$indexField]] = $tmpRow;
        }

        return $mappedCsv;
    }

    /**
     * @return array
     */
    private function getExportRequestParams()
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
