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
     * @param string $fileName
     * @param int $position
     * @param int $step
     * @return \array
     */
    public function readRecords($fileName, $position, $step)
    {
        $handle = fopen($fileName, 'r');

        if ($handle === false) {
            throw new \Exception("Can not open file $fileName");
        }

        $columnNames = fgetcsv($handle, 0, ';');

        //removes UTF-8 BOM
        foreach ($columnNames as $index => $name) {
            $columnNames[$index] = str_replace("\xEF\xBB\xBF", '', $name);
        }

        $readRows = array();
        $frame = $position + $step;
        $counter = 0;

        while ($row = fgetcsv($handle, 0, ';')) {

            if ($counter >= $position && $counter < $frame) {
                foreach ($columnNames as $key => $name) {
                    $data[$name] = isset($row[$key]) ? $row[$key] : '';
                }
                $readRows[] = $data;
            }

            if ($counter > $frame) {
                break;
            }

            $counter++;
        }

        fclose($handle);

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
     * @param string $fileName
     * @return int
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

}
