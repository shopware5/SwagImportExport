<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibility of this class is to restructure the flat array to tree and vise versa
 */
class TreeTransformer implements DataTransformerAdapter
{

    private $config;
    private $iterationPart;

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
        $tree = json_decode($this->config, true);

        if ($this->iterationPart == null) {
            $this->findIterationPart($tree);
        }
        
        $iterationPart = $this->iterationPart;
        
        $transformData = array();

        //prepares the tree for xml convert
        foreach ($data as $record) {
            $transformData[] = $this->transform($iterationPart, $record);
        }
        
        //creates 
        $treeBody = array($iterationPart['name'] => $transformData);
        
        return $treeBody;
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
     * Preparing/Modifying nodes to converting into xml
     * and puts db data into this formated array
     * 
     * @param array $node
     * @param array $mapper
     * @return array
     */
    public function transform($node, $mapper)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attribute'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transform($child, $mapper);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attribute'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }

                $currentNode['_value'] = $mapper[$node['shopwareField']];
            } else {
                $currentNode = $mapper[$node['shopwareField']];
            }
        }

        return $currentNode;
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
