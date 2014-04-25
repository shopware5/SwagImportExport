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
        $iterationPart = $this->getIterationPart();

        $transformData = array();

        //prepares the tree for xml convert
        foreach ($data as $record) {
            $transformData[] = $this->transformToTree($iterationPart, $record);
        }

        //creates iteration array
        $treeBody = array($iterationPart['name'] => $transformData);

        return $treeBody;
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
    public function transformToTree($node, $mapper = null)
    {
        if (isset($node['children'])) {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $currentNode['_attributes'][$attribute['name']] = $mapper[$attribute['shopwareField']];
                }
            }

            foreach ($node['children'] as $child) {
                $currentNode[$child['name']] = $this->transformToTree($child, $mapper);
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
        $iterationPart = $this->getIterationPart();

        $this->generateMapper($iterationPart);

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

    protected function removeIterationPart(&$node)
    {
        if (isset($node['type']) && $node['type'] === 'record') {
            $node = array('name' => '_currentMarker');
        }

        if (isset($node['children'])) {
            foreach ($node['children'] as &$child) {
                $this->removeIterationPart($child);
            }
        }
    }

}
