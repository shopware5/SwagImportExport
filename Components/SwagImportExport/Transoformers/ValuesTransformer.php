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
        $conversions = array();

        //conversions mapper
        foreach ($this->config as $expression) {
            $conversions[$expression->getVariable()] = $expression->getExportConversion();
        }

        foreach ($data as &$record) {
            foreach ($conversions as $variableName => $conversion) {
                if (isset($record[$variableName])) {                    
                    $record[$variableName] = $this->evaluator->evaluate($conversion, $record);
                }
            }
        }

        return $data;
    }

    /**
     * Maps the values by using the config import smarty fields and returns the new array
     */
    public function transformBackward($data)
    {
        
    }

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
