<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibilty of this class is to modify the values of the data values due to given user small scripts.
 */
class ValuesTransformer implements DataTransformerAdapter
{

    private $config;

    /**
     * The $config must contain the smarty or php transormation of values.
     */
    public function initialize($config)
    {
        $this->config = $config;
    }

    /**
     * Maps the values by using the config export smarty fields and returns the new array
     */
    public function transformForward($data)
    {
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
    public function composeHeader($data)
    {
        
    }

    /**
     * Does nothing in this class
     */
    public function composeFooter($data)
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
