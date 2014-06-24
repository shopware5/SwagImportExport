<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

/**
 * The responsibility of this class is to restructure the flat array to tree and vise versa
 */
class TreeTransformer implements DataTransformerAdapter
{

    protected $config;

    /**
     * @var array
     */
    protected $iterationPart;

    /**
     * @var array
     */
    protected $headerFooterData;
    
    protected $bufferData;
    protected $importMapper;
    
    //export properties
    protected $data;
    protected $currentRecord;
    protected $preparedData;

    /**
     * Sets the config that has the tree structure
     */
    public function initialize($config)
    {
        $jsonTree = '{ 
                        "name": "Root", 
                        "children": [{ 
                            "name": "Header", 
                            "children": [{ 
                                "name": "HeaderChild" 
                            }] 
                        },{
                            "name": "Articles", 
                            "children": [{ 
                                "name": "Article",
                                "adapter": "articles",
                                "attributes": [{ 
                                    "name": "Attribute1",
                                    "shopwareField": "variantId"
                                },{ 
                                    "name": "Attribute2",
                                    "shopwareField": "articleId"
                                }],
                                "children": [{ 
                                    "name": "Id",
                                    "shopwareField": "variantId"
                                },{ 
                                    "name": "Title",
                                    "shopwareField": "name",
                                    "attributes": [{ 
                                        "name": "Attribute3",
                                        "shopwareField": "lang"
                                    }]
                                },{
                                    "name": "Prices",
                                    "children": [{ 
                                        "name": "Price",
                                        "adapter": "prices",
                                        "parentKey": "variantId",
                                        "forentKey": "variantId",
                                        "attributes": [{ 
                                            "name": "groupName",
                                            "shopwareField": "pricegroup"
                                        }],
                                        "children": [{
                                            "name": "groupName",
                                            "shopwareField": "pricegroup"
                                        }, {
                                            "name": "price",
                                            "shopwareField": "netPrice"
                                        }]
                                    }]
                                }]
                            }]
                        }] 
                    }';
        $this->config = $jsonTree;
//        $this->config = $config;
    }

    /**
     * Transforms the flat array into tree with list of nodes containing children and attributes.
     */
    public function transformForward($data)
    {
        $this->setData($data);
        $transformData = array();
               
        $iterationPart = $this->getIterationPart();
        
        $adapter = $iterationPart['adapter'];
        
        foreach ($data[$adapter] as $record) {
            $this->currentRecord = $record;
            $transformData[] = $this->transformToTree($iterationPart, $record, $adapter);
            unset($this->currentRecord);
        }
        
        //creates iteration array
        $treeBody = array($iterationPart['name'] => $transformData);
        
        return $treeBody;
        
    }
    
    public function buildIterationNode($node)
    {
        $transformData = array();
        
        if ($this->currentRecord === null) {
            throw new \Exception('Current record was not found');
        }
        
        if (!isset($node['adapter'])) {
            throw new \Exception('Adapter was not found');            
        }
        
        if (!isset($node['parentKey'])) {
            throw new \Exception('Parent key was not found');            
        }
        
        $type = $node['adapter'];
        $parentKey = $node['parentKey'];
        
        //gets connections between interation nodes
        $recordLink = $this->currentRecord[$parentKey];
        
        //prepares raw data
        $data = $this->getPreparedData($type, $parentKey);
        
        //gets records for the current iteration node
        $records = $data[$recordLink];
        
        if (!$records) {
            throw new \Exception('No records were found');
        }

        foreach ($records as $record) {
            $transformData[] = $this->transformToTree($node, $record, $type);
        }
        
        return $transformData;
    }
    
    /**
     * Transforms a list of nodes containing children and attributes into flat array.
     */
    public function transformBackward($data)
    {
        $importMapper = $this->getImportMapper();

        foreach ($data as $record) {
            $this->bufferData = array();
            $this->transformFromTree($record, $importMapper);

            $rawData[] = $this->bufferData;
        }

        return $rawData;
    }
    
    /**
     * Composes a tree header based on config
     */
    public function composeHeader()
    {
        $data = $this->getHeaderAndFooterData();

        return $data;
    }

    /**
     * Composes a tree footer based on config
     */
    public function composeFooter()
    {
        $data = $this->getHeaderAndFooterData();

        return $data;
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
     * Preparing/Modifying nodes to converting into xml
     * and puts db data into this formated array
     * 
     * @param array $node
     * @param array $mapper
     * @return array
     */
    public function transformToTree($node, $mapper = null, $adapterType = null)
    {
        if (isset($node['adapter']) && $node['adapter'] != $adapterType) {
            $currentNode = $this->buildIterationNode($node);
            
            return $currentNode;
        }

        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformToTree($child, $mapper, $node['adapter']);
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

    public function transformFromTree($node, $importMapper, $parent = null)
    {
        if (isset($node['_value'])) {
            $this->saveBufferData($importMapper[$parent], $node['_value']);

            if (isset($node['_attributes'])) {
                $this->transformFromTree($node['_attributes'], $importMapper, $parent);
            }
        } else if (is_array($node)) {
            foreach ($node as $key => $child) {
                $this->transformFromTree($child, $importMapper, $key);
            }
        } else {
            $this->saveBufferData($importMapper[$parent], $node);
        }
    }
    
    /**
     * Search the iteration part of the tree template
     * 
     * @param array $tree
     */
    public function findIterationPart(array $tree)
    {
        foreach ($tree as $key => $value) {
            if ($key === 'adapter') {
                $this->iterationPart = $tree;
                return;
            }
            
            if (is_array($value)) {
                $this->findIterationPart($value);
            }
        }

        return;
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

    public function getHeaderAndFooterData()
    {
        if ($this->headerFooterData === null) {
            $tree = json_decode($this->config, true);
            //replaceing iteration part with custom marker
            $this->removeIterationPart($tree);
            
            $modifiedTree = $this->transformToTree($tree);
            
            if (!isset($tree['name'])) {
                throw new \Exception('Root category in the tree does not exists');
            }
            
            $this->headerFooterData = array($tree['name'] => $modifiedTree);
        }
        
        return $this->headerFooterData;
    }

    /**
     * Returns import mapper
     * 
     * @return array
     */
    public function getImportMapper()
    {
        if ($this->importMapper === null) {
            $iterationPart = $this->getIterationPart();
            $this->generateMapper($iterationPart);
        }

        return $this->importMapper;
    }

    /**
     * @param string $key
     * @param string $value
     * @throws \Exception
     */
    public function saveMapper($key, $value)
    {
        if (isset($this->importMapper[$key])) {
            throw new \Exception("Import mapper key $key already exists");
        }

        $this->importMapper[$key] = $value;
    }

    /**
     * @param string $key
     * @param string $value
     * @throws \Exception
     */
    public function saveBufferData($key, $value)
    {
        $this->bufferData[$key] = $value;
    }
    
    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }
    
    public function getPreparedData($type, $recordLink)
    {
        if ($this->preparedData[$type] === null) {
            $data = $this->getData();

            foreach ($data[$type] as $record) {
                $this->preparedData[$type][$record[$recordLink]][] = $record;
            }           
        }
        
        return $this->preparedData[$type];
    }
        
    /**
     * Generates import mapper from the js tree
     * 
     * @param mixed $node
     */
    protected function generateMapper($node)
    {
        if (isset($node['children'])) {

            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attr) {
                    $this->saveMapper($attr['name'], $attr['shopwareField']);
                }
            }

            foreach ($node['children'] as $child) {
                $this->generateMapper($child);
            }
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attr) {
                    $this->saveMapper($attr['name'], $attr['shopwareField']);
                }
            }

            $this->saveMapper($node['name'], $node['shopwareField']);
        }
    }

    /**
     * Replace the iteration part with custom tag "_currentMarker"
     * 
     * @param mixed $node
     */
    protected function removeIterationPart(&$node)
    {
        if (isset($node['adapter'])) {
            $node = array('name' => '_currentMarker');
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->removeIterationPart($child);
            }
        }
    }

}
