<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\FileIO;

use SwagImportExport\Components\Converter\XmlConverter;
use SwagImportExport\Components\Utils\FileHelper;

/**
 * This class is responsible to generate XML file or portions of an XML file on the hard disk.
 * The input data must be in php array forming a tree-like structure
 */
class XmlFileWriter implements FileWriter
{
    protected bool $treeStructure = true;

    protected XmlConverter $xmlConvertor;

    protected FileHelper $fileHelper;

    public function __construct(FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
        $this->xmlConvertor = new XmlConverter();
    }

    /**
     * Writes the header data in the file. The header data should be in a tree-like structure.
     *
     * @throws \Exception
     */
    public function writeHeader(string $fileName, array $headerData): void
    {
        $dataParts = $this->splitHeaderFooter($headerData);
        $this->getFileHelper()->writeStringToFile($fileName, $dataParts[0]);
    }

    /**
     * Writes records in the file. The data must be a tree-like structure.
     * The header of the file must be already written on the harddisk,
     * otherwise the xml fill have an invalid format.
     *
     * @throws \Exception
     */
    public function writeRecords(string $fileName, array $data): void
    {
        //converting the whole template tree without the interation part
        $data = $this->xmlConvertor->_encode($data);

        $this->getFileHelper()->writeStringToFile($fileName, \trim($data), \FILE_APPEND);
    }

    /**
     * Writes the footer data in the file. These are usually some closing tags -
     * they should be in a tree-like structure.
     *
     * @throws \Exception
     */
    public function writeFooter(string $fileName, ?array $footerData): void
    {
        $dataParts = $this->splitHeaderFooter($footerData ?? []);

        $data = isset($dataParts[1]) ? $dataParts[1] : null;

        $this->getFileHelper()->writeStringToFile($fileName, $data, \FILE_APPEND);
    }

    public function hasTreeStructure(): bool
    {
        return $this->treeStructure;
    }

    public function getFileHelper(): FileHelper
    {
        return $this->fileHelper;
    }

    /**
     * Splitting the tree into two parts
     *
     * @return array<string>
     *
     * @throws \Exception
     */
    protected function splitHeaderFooter(array $data): array
    {
        //converting the whole template tree without the iteration part
        $data = $this->xmlConvertor->encode($data);

        //spliting the the tree in to two parts
        return \explode('<_currentMarker></_currentMarker>', $data);
    }
}
