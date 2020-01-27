<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\FileIO\Encoders\CsvEncoder;
use Shopware\Components\SwagImportExport\Utils\FileHelper;

class CsvFileWriter implements FileWriter
{
    protected $treeStructure = false;

    /**
     * @var FileHelper
     */
    protected $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

    /**
     * @param string $fileName
     * @param array  $headerData
     *
     * @throws \Exception
     * @throws \Exception
     */
    public function writeHeader($fileName, $headerData)
    {
        if (!is_array($headerData)) {
            throw new \Exception('Header data is not valid');
        }
        $columnNames = implode(';', $headerData) . "\n";
        $this->getFileHelper()->writeStringToFile($fileName, $columnNames);
    }

    /**
     * @param $fileName
     * @param $data
     *
     * @throws \Exception
     */
    public function writeRecords($fileName, $data)
    {
        $flatData = '';

        $convertor = new CsvEncoder();
        $keys = array_keys(current($data));
        foreach ($data as $line) {
            $flatData .= $convertor->_encode_line($line, $keys) . $convertor->sSettings['newline'];
        }
        $this->getFileHelper()->writeStringToFile($fileName, $flatData, FILE_APPEND);
    }

    /**
     * @param $fileName
     * @param $footerData
     */
    public function writeFooter($fileName, $footerData)
    {
    }

    /**
     * @return bool
     */
    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    /**
     * @return FileHelper
     */
    public function getFileHelper()
    {
        return $this->fileHelper;
    }
}
