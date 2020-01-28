<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\UploadPathProvider;

class CsvFileReader implements FileReader
{
    /**
     * @var bool
     */
    protected $treeStructure = false;

    /**
     * @var UploadPathProvider
     */
    private $uploadPathProvider;

    public function __construct(UploadPathProvider $uploadPathProvider)
    {
        $this->uploadPathProvider = $uploadPathProvider;
    }

    /**
     * Reads csv records
     *
     * @param string $fileName
     * @param int    $position
     * @param int    $step
     *
     * @throws \Exception
     *
     * @return array
     */
    public function readRecords($fileName, $position, $step)
    {
        // Make sure to detect CR LF (Windows) line breaks
        ini_set('auto_detect_line_endings', true);

        $tempFileName = '';
        if (file_exists($fileName)) {
            $tempFileName = $this->uploadPathProvider->getRealPath(md5(microtime() . '.csv'));
            file_put_contents($tempFileName, file_get_contents($fileName));
            $fileName = $tempFileName;
        }

        $file = new \SplFileObject($fileName);
        $file->setCsvControl(';', '"');
        $file->setFlags(\SplFileObject::READ_CSV);

        $columnNames = $this->getColumnNames($file);

        //moves the file pointer to a certain line
        // +1 to ignore the first line of the file
        $file->seek($position + 1);

        $readRows = [];
        $data = [];
        for ($i = 1; $i <= $step; ++$i) {
            $row = $file->current();

            if ($this->isInvalidRecord($row)) {
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

        return $this->toUtf8($readRows);
    }

    /**
     * @return bool
     */
    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    /**
     * @param array $tree
     */
    public function setTree($tree)
    {
    }

    /**
     * Counts total rows of the entire CSV file
     *
     * @throws \Exception
     *
     * @return int
     */
    public function getTotalCount($fileName)
    {
        $fileHandler = fopen($fileName, 'rb');

        if ($fileHandler === false) {
            throw new \Exception("Can not open file $fileName");
        }
        $counter = 0;

        while ($row = fgetcsv($fileHandler, 0, ';')) {
            if ($this->isInvalidRecord($row)) {
                continue;
            }
            ++$counter;
        }

        fclose($fileHandler);

        //removing first row /column names/
        return $counter - 1;
    }

    /**
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

    /**
     * Returns column names of the given CSV file
     *
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
     * @param array $row
     *
     * @return bool
     */
    private function isInvalidRecord($row)
    {
        return $row[0] === null;
    }
}
