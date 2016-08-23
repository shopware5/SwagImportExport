<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO;

class CsvFileReader implements FileReader
{
    /**
     * @var bool $treeStructure
     */
    protected $treeStructure = false;

    /**
     * @param $fileName
     */
    public function readHeader($fileName)
    {
    }

    /**
     * Reads csv records
     *
     * @param $fileName
     * @param $position
     * @param $step
     * @return array
     * @throws \Exception
     */
    public function readRecords($fileName, $position, $step)
    {
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        if ($mediaService->has($fileName)) {
            $tempFileName = Shopware()->DocPath() . 'media/temp/' . md5(microtime()) . '.csv';
            file_put_contents($tempFileName, $mediaService->read($fileName));
            $fileName = $tempFileName;
        }

        $file = new \SplFileObject($fileName);
        $file->setCsvControl(";", '"');
        $file->setFlags(\SplFileObject::READ_CSV);

        $columnNames = $this->getColumnNames($file);

        //moves the file pointer to a certain line
        // +1 to ignore the first line of the file
        $file->seek($position + 1);

        $readRows = array();
        for ($i = 1; $i <= $step; $i++) {
            $row = $file->current();

            if (!$file->valid()) {
                break;
            }

            foreach ($columnNames as $key => $name) {
                $data[$name] = isset($row[$key]) ? $row[$key] : '';
            }

            $readRows[] = $data;

            // Move the pointer to the next line
            $file->next();
        }

        unlink($tempFileName);

        $readRows = $this->toUtf8($readRows);

        return $readRows;
    }

    /**
     * @param $fileName
     */
    public function readFooter($fileName)
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
     * Counts total rows of the entire CSV file
     *
     * @param $fileName
     * @return int
     * @throws \Exception
     */
    public function getTotalCount($fileName)
    {
        $handle = fopen($fileName, 'r');

        if ($handle === false) {
            throw new \Exception("Can not open file $fileName");
        }
        $counter = 0;

        while ($row = fgetcsv($handle, 0, ';')) {
            $counter++;
        }

        fclose($handle);

        //removing first row /column names/
        return $counter - 1;
    }

    /**
     * Returns column names of the given CSV file
     *
     * @param \SplFileObject $file
     * @return array
     */
    private function getColumnNames(\SplFileObject $file)
    {
        //Rewinds to first line
        $file->rewind();
        $columnNames = $file->current();

        //removes UTF-8 BOM
        foreach ($columnNames as $index => $name) {
            $columnNames[$index] = str_replace("\xEF\xBB\xBF", '', $name);
        }

        return $columnNames;
    }

    /**
     * @param array $rows
     * @return array
     */
    protected function toUtf8(array $rows)
    {
        // detect whether the input is UTF-8 or ISO-8859-1
        array_walk_recursive(
            $rows,
            function (&$value) {
                // will fail, if special chars are encoded to latin-1
                // $isUtf8 = (utf8_encode(utf8_decode($value)) == $value);

                // might have issues with encodings other than utf-8 and latin-1
                $isUtf8 = (mb_detect_encoding($value, 'UTF-8', true) !== false);
                if (!$isUtf8) {
                    $value = utf8_encode($value);
                }

                return $value;
            }
        );

        return $rows;
    }
}
