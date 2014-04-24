<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibility of this class is to restructure the flat array to tree and vise versa
 */
class FlattenTransformer implements DataTransformerAdapter
{

    private $config;
    private $iterationPart;
    private $tempData = array();

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
        $xmlString = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><shopware>' . $data . '</shopware>';
        
        $iterationPart = $this->getIterationPart();
        $iterationTag = $iterationPart['name'];
              
        $xml = new \SimpleXMLElement($xmlString);

        foreach ($xml->{$iterationTag} as $element) {
            $this->tempData = array();
            $this->collectData($element);
            $flatData .= implode(';', $this->tempData) . "\n";
        }
        
        return $flatData;
    }

    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     */
    public function transformBackward($data)
    {
        
    }

    /**
     * Composes a header column names based on config
     */
    public function composeHeader()
    {
        $iterationPart = $this->getIterationPart();
        $transformData = $this->transform($iterationPart);
        $transformData = array($iterationPart['name'] => $transformData);
        
        $this->collectHeader($transformData);
        
        $columnNames .= implode(';', $this->tempData) . "\n";
        
        return $columnNames;
    }

    /**
     * Composes a tree footer based on config
     */
    public function composeFooter()
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

    /**
     * Search the iteration part of the tree template
     * 
     * @param array $tree
     * @return array
     */
    public function findIterationPart(array $tree)
    {
        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                $this->findIterationPart($value);
            }

            if ($key == 'type' && $value == 'record') {
                $this->iterationPart = $tree;
                return;
            }
        }

        return;
    }

    /**
     * 
     * @param simplexml object $node
     */
    public function collectData($node)
    {
        if ($this->hasChildren($node)) {
            if ($this->hasAttributes($node)) {
                foreach ($node->attributes() as $attr) {
                    $this->saveTempData($attr->__toString());
                }
            }

            foreach ($node->children() as $key => $child) {
                $this->collectData($child);
            }
        } else {
            if ($this->hasAttributes($node)) {
                foreach ($node->attributes() as $attr) {
                    $this->saveTempData($attr->__toString());
                }
            }
            $this->saveTempData($node->__toString());
        }
    }

    /**
     * Preparing/Modifying nodes to converting into xml
     * and puts db data into this formated array
     * 
     * @param array $node
     * @param array $mapper
     * @return array
     */
    public function transform($node, $mapper = null)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transform($child, $mapper);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }

                $currentNode['_value'] = $mapper[$node['shopwareField']];
            } else {
                $currentNode = $mapper[$node['shopwareField']];
            }
        }

        return $currentNode;
    }
    
    /**
     * Returns the iteration part of the tree
     * 
     * @return array
     */
    public function getIterationPart()
    {
        if ($this->iterationPart == null) {
            $tree = json_decode($this->config, true);
            $this->findIterationPart($tree);
        }

        return $this->iterationPart;
    }
    
    /**
     * Creates columns name for the csv file
     * 
     * @param array $node
     * @param string $nodeKey
     */
    public function collectHeader($node, $nodeKey = null)
    {
        $dot = $nodeKey ? '.' : null;
        
        if (is_array($node)) {
            foreach ($node as $key => $value) {
                if ($key == '_attributes') {
                    foreach($value as $keyAttr => $attr){
                        $this->saveTempData($nodeKey . '_' . $keyAttr);
                    }
                } else {
                    if ($key == '_value'){
                        $this->collectHeader($value, $nodeKey);
                    } else {
                        $this->collectHeader($value, $nodeKey . $dot . $key);                        
                    }
                }
            }
        } else {
            $this->saveTempData($nodeKey);
        }
    }

    /**
     * @param \SimpleXMLElement $node
     * @return boolean
     */
    private function hasChildren(\SimpleXMLElement $node)
    {
        if (count($node->children()) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param \SimpleXMLElement $node
     * @return boolean
     */
    private function hasAttributes(\SimpleXMLElement $node)
    {
        if (count($node->attributes()) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param string $data
     */
    private function saveTempData($data)
    {
        $this->tempData[] = $data;
    }

}
