<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibility of this class is to restructure the flat array to tree and vise versa
 */
class FlattenTransformer implements DataTransformerAdapter
{

    protected $config;
    protected $mainIterationPart;
    protected $mainAdapter;
    protected $iterationParts;
    protected $iterationTempData;
    protected $tempData = array();

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
        $mainNode = $this->getMainIterationPart();
        $this->processIterationParts($mainNode);

        $nodeName = $mainNode['name'];

        foreach ($data[$nodeName] as $record) {
            $this->tempData = array();
            $this->collectData($record, $nodeName);
            $flatData[] = $this->tempData;
        }
        return $flatData;
    }

    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     */
    public function transformBackward($data)
    {
        $iterationPart = $this->getMainIterationPart();
        
        foreach ($data as $row) {
            $tree[] = $this->transformToTree($iterationPart, $row, $iterationPart['name']);
        }
        
        return $tree;
    }

    /**
     * Composes a header column names based on config
     */
    public function composeHeader()
    {
        $mainNode = $this->getMainIterationPart();
        $this->processIterationParts($mainNode);
        
        $transformData = $this->transform($mainNode);
        $this->collectHeader($transformData, $mainNode['name']);
        
        return $this->tempData;
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
    public function findMainIterationPart(array $tree)
    {
        foreach ($tree as $key => $value) {
            if ($key === 'adapter') {
                $this->mainIterationPart = $tree;
                return;
            }

            if (is_array($value)) {
                $this->findMainIterationPart($value);
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
     * Transform flat data into tree array
     * 
     * @param mixed $node
     * @param array $data
     * @return array
     */
    public function transformToTree($node, $data, $nodePath = null)
    {
        $currentPath = null;
        
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentPath = $nodePath . '_' . $attribute['name'];
                    $currentNode['_attributes'][$attribute['name']] = $this->getDataValue($data, $currentPath);
                }
            }

            foreach ($node['children'] as $child) {
                $currentPath = $nodePath . '.' .$child['name'];
                $currentNode[$child['name']] = $this->transformToTree($child, $data, $currentPath);
            }
        } else {
            if (isset($node['attributes'])) {
                
                foreach ($node['attributes'] as $attribute) {
                    $currentPath = $nodePath . '_' . $attribute['name'];
                    $currentNode['_attributes'][$attribute['name']] = $this->getDataValue($data, $currentPath);
                }

                $currentNode['_value'] = $this->getDataValue($data, $nodePath);
            } else {
                $currentNode = $this->getDataValue($data, $nodePath);
            }
        }

        return $currentNode;
    }

    /**
     * Returns data from the CSV
     * If data don't match with the csv column names, throws exception
     * 
     * @param array $data
     * @param string $nodePath
     * @return mixed
     * @throws \Exception
     */
    public function getDataValue($data, $nodePath)
    {
        if (!isset($data[$nodePath])){
            throw new \Exception("Data does not match with CSV column name $nodePath");
        }
        
        return $data[$nodePath];
    }

    /**
     * Creates columns name for the csv file
     * 
     * @param array $node
     * @param string $nodeKey
     */    
    public function collectHeader($node, $path)
    {
        if ($this->iterationParts[$path] == 'price'){
            //todo: prices
        } elseif ($this->iterationParts[$path] == 'configurator') {
            //todo: configurator
        } else {
            foreach ($node as $key => $value) {
                
                if (is_array($value)) {
                    $currentPath = $path . '/' . $key;
                    $this->collectHeader($value, $currentPath);
                } else {
                    if ($key == '_value') {
                        $pathParts = explode('/',$path);
                        $this->saveTempData($pathParts[count($pathParts) - 1]);
                    } else {
                        $this->saveTempData($key);                        
                    }
                }
            }
        }
    }

    /**
     * Collects record data
     * 
     * @param mixed $node
     * @param string $path
     */
    public function collectData($node, $path)
    {
        if (isset($this->iterationParts[$path]) && $this->iterationParts[$path] != $this->getMainAdapter()){
            if ($this->iterationParts[$path] == 'price'){
                //todo: prices
            } elseif ($this->iterationParts[$path] == 'configurator') {
                //todo: configurator
            } else {
                //processing images, similars and propertyValues
                foreach ($node as $value) {
                    $this->collectIterationData($value);
                }
                
                foreach ($this->getIterationTempData() as $tempData) {
                    if (is_array($tempData)) {
                        $data = implode('|', $tempData);
                        $this->saveTempData($data);
                    }
                }
                unset($this->iterationTempData);
            }
            
        } else {
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $currentPath = $path . '/' . $key;
                    $this->collectData($value, $currentPath);
                } else {
                    $this->saveTempData($value);
                }
            }            
        }
        
    }
    
    public function collectIterationData($node, $path = null)
    {        
        foreach ($node as $key => $value) {
            if ($path) {
                 $currentPath = $path . '/' . $key;
            } else {
                $currentPath = $key;
            } 
                
            if (is_array($value)) {
                $this->collectIterationData($value, $currentPath);
            } else {
                $this->saveIterationTempData($currentPath, $value);
            }
        }
    }
        
    /**
     * Returns the iteration part of the tree
     * 
     * @return array
     */
    public function getMainIterationPart()
    {
        if ($this->mainIterationPart == null) {
            $tree = json_decode($this->config, true);
            $this->findMainIterationPart($tree);
        }

        return $this->mainIterationPart;
    }
    
    public function getMainAdapter()
    {
        if ($this->mainAdapter == null) {
            $mainIterationPart = $this->getMainIterationPart();
            $this->mainAdapter = $mainIterationPart['adapter'];
        }
        
        return $this->mainAdapter;
    }
    
    /**
     * Finds and saves iteration parts
     * 
     * @param array $nodes
     * @param string $path
     */
    public function processIterationParts($nodes, $path = null)
    {
        foreach ($nodes as $key => $node) {
            
            if (isset($nodes['name'])) {
                if ($path) {
                    $currentPath = $path . '/' . $nodes['name'];
                } else {
                    $currentPath = $nodes['name'];
                }                
            } else {
                $currentPath = $path;
            }
            
            if ($key == 'type' && $node == 'iteration') {
                
                $this->saveIterationParts($currentPath, $nodes['adapter']);
            }
            
            if (is_array($node)) {
                $this->processIterationParts($node, $currentPath);
            }
        }
    }
    
    public function saveIterationTempData($currentPath, $value)
    {
        $this->iterationTempData[$currentPath][] = $value;
    }
    
    public function getIterationTempData()
    {
        return $this->iterationTempData;
    }
    
    public function unsetIterationTempData()
    {
        unset($this->iterationTempData);
    }
    
    /**
     * Saves interation parts
     * 
     * @param string $path
     * @param string $adapter
     */
    public function saveIterationParts($path, $adapter)
    {
        $this->iterationParts[$path] = $adapter;
    }

    /**
     * @param string $data
     */
    public function saveTempData($data)
    {
        $this->tempData[] = $data;
    }

}
