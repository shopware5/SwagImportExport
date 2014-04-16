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
        
        //creates iteration array
        $treeBody = array($iterationPart['name'] => $transformData);
        
        //todo: run xml convertor here ?
        $treeBody = $this->convertToXml($treeBody);
        
        return trim($treeBody);
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
     * Transforms a list of nodes containing children and attributes into flat array.
     */
    public function transformBackward($data)
    {
        
    }

    /**
     * Composes a tree header based on config
     */
    public function composeHeader()
    {
        $xmlData = $this->splitTree('header');
        return $xmlData;
    }

    /**
     * Composes a tree footer based on config
     */
    public function composeFooter()
    {
        $xmlData = $this->splitTree('footer');
        return $xmlData;
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
     * Spliting the tree into two parts
     * 
     * @param string $part
     * @return string
     * @throws \Exception
     */
    private function splitTree($part)
    {
        $tree = json_decode($this->config, true);

        //replaceing iteration part with custom marker
        $this->removeIterationPart($tree);
        $data = $this->transform($tree);

        //converting the whole template tree without the interation part
        $convert = new \Shopware_Components_Convert_Xml();
        $data = $convert->encode(array('root' => $data));

        //spliting the the tree in to two parts
        $treeParts = explode('<_currentMarker></_currentMarker>', $data);

        switch ($part) {
            case 'header':
                return $treeParts[0];
            case 'footer':
                return $treeParts[1];
            default:
                throw new \Exception("Tree part $part does not exists.");
        }
    }

    private function removeIterationPart(&$node)
    {
        if (isset($node['type']) && $node['type'] === 'record') {
            $node = array('name'=>'_currentMarker');
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->removeIterationPart($child);
            }
        }
        
    }
    
    private function convertToXml($data)
    {
        $convert = new \Shopware_Components_Convert_Xml();
        $convertData = $convert->_encode($data);
        
        return $convertData;
    }
    
}
