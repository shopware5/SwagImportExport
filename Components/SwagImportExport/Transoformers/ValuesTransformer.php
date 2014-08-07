<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibilty of this class is to modify the values of the data values due to given user small scripts.
 */
class ValuesTransformer implements DataTransformerAdapter
{

    /**
     * @var Shopware\CustomModels\ImportExport\Expression
     */
    private $config;

    /**
     * @var object 
     */
    private $evaluator;

    /**
     * The $config must contain the smarty or php transormation of values.
     */
    public function initialize($config)
    {
        $this->config = $config['expression'];
        $this->evaluator = $config['evaluator'];
    }

    /**
     * Maps the values by using the config export smarty fields and returns the new array
     * 
     * @param array $data
     * @return array
     */
    public function transformForward($data)
    {
        $data = $this->transform('export', $data);
        
        return $data;
    }

    /**
     * Changes and returns the new values, before importing
     * 
     * @param array $data
     * @return array
     */
    public function transformBackward($data)
    {
        $data = $this->transform('import', $data);
        
        return $data;
    }
    
    /**
     * @param string $type
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function transform($type, $data)
    {
        $conversions = array();

        switch ($type) {
            case 'export':
                $method = 'getExportConversion';
                break;
            case 'import':
                $method = 'getImportConversion';
                break;
            default:
                throw new \Exception("Convert type $type does not exists.");
        }
        
        foreach ($this->config as $expression) {
            $conversions[$expression->getVariable()] = $expression->{$method}();
        }
                
        if (!empty($conversions)) {
            foreach ($data as &$records) {
                foreach ($records as &$record) {
                    foreach ($conversions as $variableName => $conversion) {
                        if (isset($record[$variableName]) && !empty($conversion)) {  
                            $evalData = $this->evaluator->evaluate($conversion, $record);
                            if ($evalData) {
                                $record[$variableName] = $this->evaluator->evaluate($conversion, $record);
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }
    
//    if we dont now the array dept we need to make it recurcive
//    the function below is will do the job, but check the speed before commiting
//    public function evaluateData(&$data, $conversions)
//    {
//        foreach ($data as $key => &$record) {
//            if (is_array($record)) {
//                $this->evaluateData($record, $conversions);
//            } else {
//                foreach ($conversions as $variableName => $conversion) {
//                    if ($key === $variableName) {
//                        $evalData = $this->evaluator->evaluate($conversion, $data);
//                        if ($evalData) {
//                            $data[$variableName] = $evalData;                            
//                        }
//                    }
//                }                
//            }
//        }
//    }

    /**
     * Does nothing in this class
     */
    public function composeHeader()
    {
        
    }

    /**
     * Does nothing in this class
     */
    public function composeFooter()
    {
        
    }

    /**
     * Does nothing in this class
     */
    public function parseHeader($data)
    {
        
    }

    /**
     * Does nothing in this class
     */
    public function parseFooter($data)
    {
        
    }

}
