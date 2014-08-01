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
    protected $tempMapper;

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
            $this->resetTempData();
            $this->collectData($record, $nodeName);
            $flatData[] = $this->getTempData();
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

        return $this->getTempData();
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
     * Creates and returns mapper of provided profile node
     * 
     * @param array $node
     * @return array
     */
    public function createMapperFromProfile($node)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $attribute['shopwareField'];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->createMapperFromProfile($child);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $attribute['shopwareField'];
                }

                $currentNode['_value'] = $node['shopwareField'];
            } else {
                $currentNode = $node['shopwareField'];
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
     * @param string $path
     */    
    public function collectHeader($node, $path)
    {
        if ($this->iterationParts[$path] == 'price'){
            $priceProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'price');
            $priceNodeMapper = $this->createMapperFromProfile($priceProfile);
            
            //only saving the price groups
//            $this->createHeaderPriceGroup($priceNodeMapper);
            
            //saving nodes different from price groups
            foreach ($this->getCustomerGroups() as $group) {
                $this->createHeaderPriceNodes($priceNodeMapper, $group->getKey());                
            }
        } elseif ($this->iterationParts[$path] == 'configurator') {
            $configuratorProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'configurator');
            $configuratorNodeMapper = $this->createMapperFromProfile($configuratorProfile);
            
            //group name, group description and group id is skipped
            $this->createHeaderConfigurator($configuratorNodeMapper);
            
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
     * Saves price nodes as column names
     * 
     * @param array $node
     * @param string $groupKey
     * @param string $path
     */
    public function createHeaderPriceNodes($node, $groupKey, $path = null)
    {
        foreach ($node as $key => $value) {

            if (is_array($value)) {
                $currentPath = $this->getMergedPath($path, $key);
                $this->createHeaderPriceNodes($value, $groupKey, $currentPath);
            } else {
                if ($value == 'priceGroup') {
                    continue;
                }
                if ($key == '_value') {
                    $pathParts = explode('/', $path);
                    $name = $pathParts[count($pathParts) - 1] . '_' . $groupKey ;
                    $this->saveTempData($name);
                } else {
                    $key .= '_' . $groupKey;
                    $this->saveTempData($key);
                }
            }
        }
    }
    
    /**
     * Saves only the prices group nodes
     * 
     * @param array $node
     * @param string $path
     */
    public function createHeaderPriceGroup($node, $path = null)
    {
        foreach ($node as $key => $value) {

            if (is_array($value)) {
                $currentPath = $path . '/' . $key;
                $this->createHeaderPriceGroup($value, $currentPath);
            } else {
                //skipping price group
                if ($value != 'priceGroup') {
                    continue;
                }
                
                if ($key == '_value') {
                    $pathParts = explode('/', $path);
                    $name = $pathParts[count($pathParts) - 1];
                    $this->saveTempData($name);
                } else {
                    $this->saveTempData($key);
                }
            }
        }
    }
    
    /**
     * Saves configurator nodes, also skipping
     * configGroupId, configGroupName and configGroupDescription
     * 
     * @param array $node
     * @param string $path
     */
    public function createHeaderConfigurator($node, $path = null)
    {
        foreach ($node as $key => $value) {

            if (is_array($value)) {
                $currentPath = $path . '/' . $key;
                $this->createHeaderConfigurator($value, $currentPath);
            } else {
                if ($value == 'configGroupId' || $value == 'configGroupName' || $value == 'configGroupDescription') {
                    continue;
                }
                
                if ($key == '_value') {
                    $pathParts = explode('/', $path);
                    $name = $pathParts[count($pathParts) - 1];
                    $this->saveTempData($name);
                } else {
                    $this->saveTempData($key);
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
                $priceProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'price');
                $priceTreeMapper = $this->createMapperFromProfile($priceProfile);
                $priceFlatMapper = $this->treeToFlat($priceTreeMapper);

                //todo: check price group flag
                foreach ($this->getCustomerGroups() as $group) {

                    $priceNode = $this->findNodeByPriceGroup($node, $group->getKey(), $priceFlatMapper);

                    if ($priceNode) {
                        $this->collectPriceData($priceNode, $priceFlatMapper);
                    } else {
                        $this->collectPriceData($priceTreeMapper, $priceFlatMapper, null, true);
                    }
                    unset($priceNode);
                }

            } elseif ($this->iterationParts[$path] == 'configurator') {
                $configuratorProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'configurator');
                $configuratorTreeMapper = $this->createMapperFromProfile($configuratorProfile);
                $configuratorFlatMapper = $this->treeToFlat($configuratorTreeMapper);
                
                foreach ($node as $key => $configurator) {
                    $this->collectConfiguratorData($configurator, $configuratorFlatMapper);
                }
                
                foreach ($this->getIterationTempData() as $tempData) {
                    if (is_array($tempData)) {
                        $data = implode('|', $tempData);
                        $this->saveTempData($data);
                    }
                }
                
                unset($this->iterationTempData);
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
                    $currentPath = $this->getMergedPath($path, $key);
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
            $currentPath = $this->getMergedPath($path, $key);
                
            if (is_array($value)) {
                $this->collectIterationData($value, $currentPath);
            } else {
                $this->saveIterationTempData($currentPath, $value);
            }
        }
    }

    /**
     * Returns price node by price group 
     * 
     * @param array $node
     * @param string $groupKey
     * @param array $mapper
     * @return array
     */
    public function findNodeByPriceGroup($node, $groupKey, $mapper)
    {
        foreach ($node as $value) {
            $priceKey = $this->getPriceGroupFromNode($value, $mapper);
            if ($priceKey == $groupKey) {
                return $value;
            }
        }

        return;
    }

    public function getPriceGroupFromNode($node, $mapper, $path = null)
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, $key);

            if (is_array($value)) {
                $result = $this->getPriceGroupFromNode($value, $mapper, $currentPath);

                if ($result) {
                    return $result;
                }
            }

            if ($mapper[$currentPath] == 'priceGroup') {
                return $value;
            }
        }
    }
    
    public function collectPriceData($node, $mapper, $path = null, $emptyResult = false)
    {
         foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, $key);

            if (is_array($value)) {
                $this->collectPriceData($value, $mapper, $currentPath, $emptyResult);
            } else {
                if ($mapper[$currentPath] != 'priceGroup') {
                    if ($emptyResult) {
                        $this->saveTempData(null);
                    } else {
                        $this->saveTempData($value);
                    }
                }
            }
        }
    }
     
   public function treeToFlat($node)
    {
        $this->resetTempMapper();
        $this->convertToFlat($node);

        return $this->getTempMapper();
    }

    protected function convertToFlat($node, $path)
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, $key);

            if (is_array($value)) {
                $this->convertToFlat($value, $currentPath);
            } else {
                $this->saveTempMapper($currentPath, $value);
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
                $currentPath = $this->getMergedPath($path, $nodes['name']);
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
    
    public function getNodeFromProfile($node, $adapter)
    {
        foreach ($node as $key => $value) {
            
            if ($key == 'adapter' && $value == $adapter) {
                return $node;
            }
            
            if (is_array($value)) {
                $seakNode = $this->getNodeFromProfile($value, $adapter);
                if ($seakNode) {
                    return $seakNode;
                }
            }
        }
    }
    
    /**
     * Returns configuration group value by given node and mapper
     * 
     * @param array $node
     * @param array $mapper
     * @param string $path
     * @return string
     */
    public function findConfigurationGroupValue($node, $mapper, $path = null)
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, $key);
            
            if (is_array($value)) {
                $result = $this->findConfigurationGroupValue($value, $mapper, $currentPath);
                
                if ($result) {
                    return $result;
                }
                
            } else {
                 if ($mapper[$currentPath] == 'configGroupName'){
                     return $value;
                 }
            }
        } 
    }
    
    /**
     * 
     * @param array $node
     * @param array $mapper
     * @param string $path
     * @param array $originalNode
     */
    public function collectConfiguratorData($node, $mapper, $path = null, $originalNode = null)
    {
        foreach ($node as $key => $value) {
            
            $currentPath = $this->getMergedPath($path, $key);
            
            if (is_array($value)) {
                $this->collectConfiguratorData($value, $mapper, $currentPath, $node);
            } else {
                if ($mapper[$currentPath] == 'configGroupName'
                    || $mapper[$currentPath] == 'configGroupDescription'
                    || $mapper[$currentPath] == 'configGroupId') {
                    continue;
                }
                
                if ($mapper[$currentPath] == 'configOptionName'){
                    $group = $this->findConfigurationGroupValue($originalNode, $mapper);
                    
                    if ($value && $group) {
                        $mixedValue = $group . ':' . $value;                        
                    }
                    
                    $this->saveIterationTempData($currentPath, $mixedValue);
                    unset($mixedValue);
                } else {
                    $this->saveIterationTempData($currentPath, $value);
                }
            }
        }
    }
    
    public function getMergedPath($path, $key)
    {
        if ($path) {
            $newPath = $path . '/' . $key;
        } else {
            $newPath = $key;
        }
        
        return $newPath;
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
    
    public function resetTempData()
    {
        $this->tempData = array();
    }
    
    public function getTempData()
    {
        return $this->tempData;
    }

    /**
     * @param string $data
     */
    public function saveTempData($data)
    {
        $this->tempData[] = $data;
    }
    
    public function resetTempMapper()
    {
        $this->tempMapper = array();
    }

    public function getTempMapper()
    {
        return $this->tempMapper;
    }

    /**
     * @param string $data
     */
    public function saveTempMapper($path, $data)
    {
        $this->tempMapper[$path] = $data;
    }

    public function getCustomerGroups()
    {
        $groups = Shopware()->Models()->getRepository('Shopware\Models\Customer\Group')->findAll();
        
        return $groups;
    }

}
