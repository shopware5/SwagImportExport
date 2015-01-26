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

        $flatData = Shopware()->Events()->filter(
                'Shopware_Components_SwagImportExport_Transoformers_FlattenTransformer_TransformForward',
                $flatData,
                array('subject' => $this)
        );

        return $flatData;
    }

    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     */
    public function transformBackward($data)
    {
        $data = Shopware()->Events()->filter(
                'Shopware_Components_SwagImportExport_Transoformers_FlattenTransformer_TransformBackward',
                $data,
                array('subject' => $this)
        );

        $mainNode = $this->getMainIterationPart();
        $this->processIterationParts($mainNode);
        
        foreach ($data as $row) {
            $tree[] = $this->transformToTree($mainNode, $row, $mainNode['name']);
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
     * Finds the name of column with price field
     * 
     * @param array $node
     * @param string $shopwareField
     * @return string|boolean
     */
    protected function findNodeByShopwareField($node, $shopwareField)
    {
        if (isset($node['shopwareField']) && $node['shopwareField'] == $shopwareField) {
            return $node['name'];
        } else {
            if (isset($node['children'])) {
                foreach($node['children'] as $child) {
                    $return = $this->findNodeByShopwareField($child, $shopwareField);
                    if ($return !== FALSE) {
                        return $return;
                    }
                }
            }
            if (isset($node['attributes'])) {
                foreach($node['attributes'] as $attribute) {
                    $return = $this->findNodeByShopwareField($attribute, $shopwareField);
                    if ($return !== FALSE) {
                        return $return;
                    }
                }
            }
        }
        return FALSE;
    }

    /**
     * Transform flat data into tree array
     * 
     * @param mixed $node
     * @param array $data
     * @return array
     */
    public function transformToTree($node, $data, $nodePath = null, $iteration = 0)
    {
        $currentPath = null;
        
        if (isset($this->iterationParts[$nodePath])) { // iteration
            $iteration++;
            if ($node['adapter'] == 'price') {
                //find name of column with *price* values
                $priceColumnName = $this->findNodeByShopwareField($node, 'price');
                if ($priceColumnName === FALSE) {
                    throw new \Exception("Price column not found");
                }
                
                $dataColumns = array_keys($data);
                $isEkGroupMissing = false;
                $prices = array();
                $matches = array();
                $groups = array();
                
                // find groups
                $priceColumns = preg_grep("/^" . $priceColumnName . "_+(.*)/i", $dataColumns);
                foreach ($priceColumns as &$columns) {
                    preg_match("/" . $priceColumnName . "_+(?P<group>.*)$/i", $columns, $matches);
                    $groups[] = $matches['group'];
                }
                
                // special case for EK group ('_EK' may be missing)
                if (!in_array('EK', $groups)) {
                    array_unshift($groups, 'EK');
                    $isEkGroupMissing = true;
                }
                
                // TODO: add filters here
                
                // extract values
                foreach ($groups as $group) {
                    // special case for EK group ('_EK' may be missing)
                    if ($group == 'EK' && $isEkGroupMissing) {
                        $group = '';
                    }
                    $prices[] = $this->transformPricesToTree($node, $data, $group);
                }
                
                return $prices;
            } else if ($node['adapter'] == 'configurator') {
                // find fields
                $columnMapper = array(
                    'configOptionName' => $this->findNodeByShopwareField($node, 'configOptionName'),
                    'configOptionId' => $this->findNodeByShopwareField($node, 'configOptionId'),
                    'configGroupName' => $this->findNodeByShopwareField($node, 'configGroupName'),
                    'configSetName' => $this->findNodeByShopwareField($node, 'configSetName'),
                    'configSetId' => $this->findNodeByShopwareField($node, 'configSetId'),
                    'configSetType' => $this->findNodeByShopwareField($node, 'configSetType'),
                );

                if ($columnMapper['configOptionId'] === false) {
                    if ($columnMapper['configOptionName'] === false) {
                        throw new \Exception("configOptionName column not found");
                    }
                    if ($columnMapper['configGroupName'] === false) {
                        throw new \Exception("configGroupName column not found");
                    }
                }

                $separator = '|';
                if ($columnMapper['configSetId'] !== false) {
                    $configSetId = explode($separator, $this->getDataValue($data, $columnMapper['configSetId']));
                    $configSetId = $this->getFirstElement($configSetId);
                }

                if ($columnMapper['configSetType'] !== false) {
                    $configSetType = explode($separator, $this->getDataValue($data, $columnMapper['configSetType']));
                    $configSetType = $this->getFirstElement($configSetType);
                }

                if ($columnMapper['configSetName'] !== false) {
                    $setNames = explode($separator, $this->getDataValue($data, $columnMapper['configSetName']));
                    $setNames = $this->getFirstElement($setNames);
                }

                $configs = array();
                $values = explode($separator, $this->getDataValue($data, $columnMapper['configOptionName']));
                $optionIds = explode($separator, $this->getDataValue($data, $columnMapper['configOptionId']));

                //creates configOptionId to have more priority than configOptionName
                $counter = $columnMapper['configOptionId'] !== false ? count($optionIds) : count($values);

                for ($i = 0; $i < $counter; $i++) {
                    $value = explode(':', $values[$i]);
                    $configs[] = $this->transformConfiguratorToTree($node, array(
                        $columnMapper['configGroupName'] => $value[0],
                        $columnMapper['configOptionName'] => $value[1],
                        $columnMapper['configOptionId'] => $optionIds[$i],
                        $columnMapper['configSetId'] => $configSetId,
                        $columnMapper['configSetType'] => $configSetType,
                        $columnMapper['configSetName'] => $setNames
                    ));
                }

                return $configs;

            } else if ($node['adapter'] == 'propertyValue') {
                $mapper = $this->createMapperFromProfile($node);

                foreach ($mapper as $key => $value) {
                    $collectedData[$key] = $this->getDataValue($data, $key);
                }

                $newData = array();
                foreach ($collectedData as $key => $groupValue) {
                    if ($mapper[$key] == 'propertyGroupName') {
                        $propertyGroupNameKey = $key;
                        $propertyGroupName = $groupValue;
                    } else {
                        $values = explode('|', $groupValue);
                        foreach ($values as $index => $value) {
                            $newData[$index][$key] = $value;
                        }
                    }
                }

                foreach ($newData as $key => $value) {
                    $newData[$key][$propertyGroupNameKey] = $propertyGroupName;
                }

                unset($propertyGroupName);
                
                return $newData;
            } else if ($node['adapter'] === 'translation') {
                $translationName = $this->findNodeByShopwareField($node, 'name');
                $translationDescription = $this->findNodeByShopwareField($node, 'description');
                $translationDescriptionLong = $this->findNodeByShopwareField($node, 'descriptionLong');
                $translationKeywords = $this->findNodeByShopwareField($node, 'keywords');
                $translationLang = $this->findNodeByShopwareField($node, 'languageId');

                $dataColumns = array_keys($data);

                $translationColumns = array();

                $translations = array();

                $translationNameColumns = preg_grep("/^" . $translationName . "_+(.*)/i", $dataColumns);
                $translationDescriptionColumns = preg_grep("/^" . $translationDescription . "_+(.*)/i", $dataColumns);
                $translationDescriptionLongColumns = preg_grep("/^" . $translationDescriptionLong . "_+(.*)/i", $dataColumns);
                $translationKeywordsColumns = preg_grep("/^" . $translationKeywords . "_+(.*)/i", $dataColumns);

                if ($translationNameColumns) {
                    $translationColumns = array_merge($translationColumns, $translationNameColumns);
                }
                if ($translationDescriptionColumns) {
                    $translationColumns = array_merge($translationColumns, $translationDescriptionColumns);
                }
                if ($translationDescriptionLongColumns) {
                    $translationColumns = array_merge($translationColumns, $translationDescriptionLongColumns);
                }
                if ($translationKeywordsColumns) {
                    $translationColumns = array_merge($translationColumns, $translationKeywordsColumns);
                }

                foreach ($translationColumns as $column) {
                    preg_match("/(?P<column>.*)_+(?P<langId>.*)$/i", $column, $matches);
                    $columnName = $matches['column'];
                    $translations[$matches['langId']][$columnName] = $data[$column];
                    if ($translationLang) {
                        $translations[$matches['langId']][$translationLang] = $matches['langId'];
                    }
                }

                return $translations;
            }else if ($node['adapter'] != $this->getMainAdapter()) {
                
                $mapper = $this->createMapperFromProfile($node);
                
                foreach ($mapper as $key => $value) {
                    $collectedData[$key] = $this->getDataValue($data, $key);
                }
                
                $newData = array();
                foreach ($collectedData as $key => $groupValue) {
                    $values = explode('|', $groupValue);
                    foreach ($values as $index => $value) {
                        $newData[$index][$key] = $value;
                    }
                }
                
                return $newData;
            }
        }

        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $this->getDataValue($data, $attribute['name'], $iteration);
                }
            }

            foreach ($node['children'] as $child) {
                $currentPath = $nodePath . '/' .$child['name'];
                $dataValue = $this->transformToTree($child, $data, $currentPath);

                if ($dataValue !== null) {
                    $currentNode[$child['name']] = $dataValue;
                }
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $this->getDataValue($data, $attribute['name'], $iteration);
                }

                $currentNode['_value'] = $this->getDataValue($data, $node['name'], $iteration);
            } else {
                $currentNode = $this->getDataValue($data, $node['name'], $iteration);
            }
        }

        return $currentNode;
    }

    /**
     * Reurns first element of the array
     * @param array $data
     * @return string
     */
    public function getFirstElement($data)
    {
        if (is_array($data)) {
            reset($data);
            return current($data);
        } else {
            return $data;
        }
    }

    /**
     * Transform flat price data into tree array
     * 
     * @param mixed $node
     * @param array $data
     * @param string $group
     * @return array
     */
    protected function transformPricesToTree($node, $data, $group)
    {
        // special case for EK group ('_EK' may be missing)
        if ($group != '') {
            $groupValue = $group;
            $groupExtension = '_' . $group;
        } else {
            $groupValue = 'EK';
            $groupExtension = '';
        }
        
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if ($attribute['shopwareField'] != 'priceGroup') {
                        $value = $this->getDataValue($data, $attribute['name'] . $groupExtension);
                    } else {
                        $value = $groupValue;
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }
            }

            // the check for group value is not done here, but on the next level (recursion)
            // because the node may have attribute(s)
            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformPricesToTree($child, $data, $group);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if ($attribute['shopwareField'] != 'priceGroup') {
                        $value = $this->getDataValue($data, $attribute['name'] . $groupExtension);
                    } else {
                        $value = $groupValue;
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }

                if ($node['shopwareField'] != 'priceGroup') {
                    $value = $this->getDataValue($data, $node['name'] . $groupExtension);
                } else {
                    $value = $groupValue;
                }
                $currentNode['_value'] = $value;
            } else {
                if ($node['shopwareField'] != 'priceGroup') {
                    $value = $this->getDataValue($data, $node['name'] . $groupExtension);
                } else {
                    $value = $groupValue;
                }
                $currentNode = $value;
            }
        }

        return $currentNode;
    }

    /**
     * Transform flat configurator data into tree array
     * 
     * @param mixed $node
     * @param array $data
     * @return array
     */
    protected function transformConfiguratorToTree($node, $data)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if (isset($data[$attribute['name']])) {
                        $value = $data[$attribute['name']];
                    } else {
                        $value = '';
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }
            }

            // the check for group value is not done here, but on the next level (recursion)
            // because the node may have attribute(s)
            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformConfiguratorToTree($child, $data);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    if (isset($data[$attribute['name']])) {
                        $value = $data[$attribute['name']];
                    } else {
                        $value = '';
                    }
                    $currentNode['_attributes'][$attribute['name']] = $value;
                }

                if (isset($data[$node['name']])) {
                    $value = $data[$node['name']];
                } else {
                    $value = '';
                }
                $currentNode['_value'] = $value;
            } else {
                if (isset($data[$node['name']])) {
                    $value = $data[$node['name']];
                } else {
                    $value = '';
                }
                $currentNode = $value;
            }
        }

        return $currentNode;
    }

    /**
     * Returns data from the CSV
     * If data don't match with the csv column names, throws exception
     * 
     * @param array $data
     * @param string $key
     * @param int $iteration
     * @param string $adapter
     * @return mixed
     * @throws \Exception
     */
    public function getDataValue($data, $key, $iteration = 0)
    {
        if (!isset($data[$key])){
            return;
//            throw new \Exception("Data does not match with CSV column name $key");
        }
        
        if ($iteration > 1) { // if it is sub iteration node
            return explode('|', $data['key']);
        }

        return $data[$key];
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
            
        } elseif ($this->iterationParts[$path] == 'translation') {
            $translationProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'translation');
            $translationNodeMapper = $this->createMapperFromProfile($translationProfile);
            
            foreach ($this->getShops() as $shop) {
                if ($shop->getId() != 1) {
                    $this->createHeaderTranslation($translationNodeMapper, $shop->getId());
                }
            }
            
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
     * Saves translation nodes and skips languageId
     * 
     * @param array $node
     * @param int $shopId
     * @param string $path
     */
    public function createHeaderTranslation($node, $shopId, $path = null)
    {
        foreach ($node as $key => $value) {

            if (is_array($value)) {
                $currentPath = $path . '/' . $key;
                $this->createHeaderTranslation($value, $shopId, $currentPath);
            } else {
                
                if ($value == 'languageId') {
                    continue;
                }
                
                if ($key == '_value') {
                    $pathParts = explode('/', $path);
                    $name = $pathParts[count($pathParts) - 1] . '_' . $shopId ;
                    $this->saveTempData($name);
                } else {
                    $key .= '_' . $shopId;
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
                    $this->collectConfiguratorData($configurator, $configuratorFlatMapper, null, $configurator);
                }

                $iterationTempData = $this->getIterationTempData();

                foreach ($iterationTempData as $key => $tempData) {
                    if (is_array($tempData)) {
                        if ($configuratorFlatMapper[$key] === 'configSetId'
                            || $configuratorFlatMapper[$key] === 'configSetType'
                            || $configuratorFlatMapper[$key] === 'configSetName'
                        ) {
                            $this->saveTempData($tempData[0]);
                        } else {
                            $data = implode('|', $tempData);
                            $this->saveTempData($data);
                        }
                    }
                }

                unset($this->iterationTempData);
            } elseif ($this->iterationParts[$path] == 'propertyValue') {
                $propertyValueProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'propertyValue');
                $propertyValueTreeMapper = $this->createMapperFromProfile($propertyValueProfile);
                $propertyValueFlatMapper = $this->treeToFlat($propertyValueTreeMapper);

                $tempCollection = array();
                foreach ($node as $propertyValue) {
                    foreach ($propertyValue as $key => $value) {
                        if ($propertyValueFlatMapper[$key] === 'propertyGroupName') {
                            $tempCollection[$key] = $value;
                        } else {
                            $tempCollection[$key][] = $value;
                        }
                    }
                }

                foreach ($tempCollection as $collection) {
                    if (is_array($collection)) {
                        $collection = implode('|', $collection);
                    }
                    $this->saveTempData($collection);
                }

                unset($tempCollection);
            } elseif ($this->iterationParts[$path] == 'translation') {
                $translationProfile = $this->getNodeFromProfile($this->getMainIterationPart(), 'translation');
                $translationTreeMapper = $this->createMapperFromProfile($translationProfile);
                $translationFlatMapper = $this->treeToFlat($translationTreeMapper);
                
                foreach ($node as $key => $translation) {
                    $this->collectTranslationData($translation, $translationFlatMapper);
                }
                
            } else {
                //processing images, similars and accessories
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
    
    /**
     * @param type $node
     * @param type $mapper
     * @param type $path
     */
    public function collectTranslationData($node, $mapper, $path = null)
    {
        foreach ($node as $key => $value) {
            $currentPath = $this->getMergedPath($path, $key);
            if (is_array($value)) {
                $this->collectTranslationData($value, $mapper, $currentPath);
            } else {
                if ($mapper[$currentPath] == 'languageId'){
                    continue;
                }
                
                $this->saveTempData($value);
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
    
    public function getShops()
    {
        $shops = Shopware()->Models()->getRepository('Shopware\Models\Shop\Shop')->findAll();
        
        return $shops;
    }
}
