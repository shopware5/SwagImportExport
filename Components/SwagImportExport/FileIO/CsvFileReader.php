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
        
        $columnNames = fgetcsv($handle);
        $columnNames = explode(';', $columnNames[0]);
        
        $readRows = array();
        $frame = $position + $step;
        $counter = 0;
        
        while ($row = fgetcsv($handle)) {
            
            if ($counter > $position && $counter <= $frame ) {
                
                $tempData = null;                
                $tempData = explode(';', $row[0]);       
                
                foreach ($columnNames as $key => $name) {
                    $data[$name] = isset($tempData[$key]) ? $tempData[$key] : '';
                }
                $readRows[] = $data;
            }
            
            if($counter > $frame){
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
        $file = file($fileName);
        
        //removing first row /column names/
        $totalRecords = count($file) - 1;
        
        return $totalRecords;
    }

}
