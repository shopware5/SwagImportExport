<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class CsvFileReader implements FileReader
{
    protected $treeStructure = false;
    
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
        $delimiter = ';';

        $file = new \SplFileObject($fileName);


        $columnNames = $this->getColumnNames($file, $delimiter);

        for ($i = 1; $i <= $step; $i++){
            $offset = $position + $i;

            //moves the file pointer to a certain line
            $file->seek($offset);

            if (!$file->valid()){
                break;
            }

            $current = trim($file->current());

            $row = explode($delimiter, $current);

            foreach ($columnNames as $key => $name) {
                $data[$name] = isset($row[$key]) ? $row[$key] : '';
            }

            $readRows[] = $data;
        }

        return $readRows;
    }

    public function readFooter($fileName)
    {
        
    }
    
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
     * @param \SplFileObject $file
     * @param $delimiter
     * @return array
     */
    private function getColumnNames(\SplFileObject $file, $delimiter)
    {
        $file->seek(0);
        $current = trim($file->current());

        $columnNames = explode($delimiter, $current);

        //removes UTF-8 BOM
        foreach ($columnNames as $index => $name) {
            $columnNames[$index] = str_replace("\xEF\xBB\xBF", '', $name);
        }

        return $columnNames;
    }

}
