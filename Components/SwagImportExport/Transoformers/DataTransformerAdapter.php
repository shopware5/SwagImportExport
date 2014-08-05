<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * This interface defines the way the transformers must work.
 * Each of them must be able to compose headers and footers, and to transform the data in both directions.
 * 
 */
interface DataTransformerAdapter
{
    
    /**
     * Sets the main config which defines the data restructuring
     */
    public function initialize($config);

    /**
     * Transforms the data in direction to formatted output file and returns the transformed data.
     */
    public function transformForward($data);

    /**
     * Transforms the data in direction from formatted output file and returns the transformed data.
     */
    public function transformBackward($data);
    
    /**
     * Composes the header of the formatted output file.
     */
    public function composeHeader();

    
    /**
     * Composes the footer of the formatted output file.
     */
    public function composeFooter();
    
    
    /**
     * Parses the header of the formatted input file data.
     */
    public function parseHeader($data);

    
    /**
     * Parses the footer of the formatted input file data.
     */
    public function parseFooter($data);
    
    
}