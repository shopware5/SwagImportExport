<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibility of this class is to restructure the flat array to tree and vise versa
 */
class TreeTransformer implements DataTransformerAdapter
{

    private $config;

    /**
     * Sets the config that has the tree structure
     */
    public function initialize($config)
    {        
        $this->config = $config;
    }

    /**
     * Transforms the flat array into tree with list of nodes containing children and attributes.
     */
    public function transformForward($data)
    {
        
    }

    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     */
    public function transformBackward($data)
    {
        
    }

    /**
     * Composes a tree header based on config
     */
    public function composeHeader($data)
    {
        
    }

    /**
     * Composes a tree footer based on config
     */
    public function composeFooter($data)
    {
        
    }

    /**
     * Parses a tree header based on config
     */
    public function parseHeader($data)
    {
        
    }

    /**
     * Parses a tree footer based on config
     */
    public function parseFooter($data)
    {
        
    }

}
