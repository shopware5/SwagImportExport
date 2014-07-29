<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class XmlFileReader implements FileReader
{

    protected $tree;
    protected $iterationPath = array();
    protected $iterationTag = array();
    /*
     * @var boolen
     */
    protected $treeStructure = true;
    protected $fileHelper;

    public function __construct($fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }

    public function setTree($tree)
    {
        $this->tree = $tree;
        $this->findIterationNode($tree, array());
    }

    protected function toArrayTree(\DOMElement $node, $path)
    {
        $hasChildren = false;
        $record = array();
        $currentPath = $path . '/' . $node->nodeName;
        
        if ($node->hasChildNodes()) {
            if (in_array($currentPath, $this->iterationPath)) {
                foreach ($node->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $hasChildren = true;
                        $record[$child->nodeName][] = $this->toArrayTree($child, $currentPath);
                    }
                }
            } else {
                foreach ($node->childNodes as $child) {
                    if ($child instanceof \DOMElement) {
                        $hasChildren = true;
                        $record[$child->nodeName] = $this->toArrayTree($child, $currentPath);
                    }
                }
            }
        }

        if ($node->hasAttributes()) {
            $record["_attributes"] = array();
            foreach ($node->attributes as $attr) {
                $record["_attributes"][$attr->name] = $attr->value;
            }
            if (!$hasChildren) {
                $record["_value"] = $node->nodeValue;
            }
        } else if (!$hasChildren) {
            $record = $node->nodeValue;
        }

        return $record;
    }

    public function readHeader($fileName)
    {
        
    }

    public function readRecords($fileName, $position, $count)
    {
        //todo: add string argument
        $reader = new \XMLReader();
        $reader->open($fileName);

        // find the first iterationNode
        foreach (explode('/', $this->iterationPath[0]) as $node) {
            $reader->next($node);
            $reader->read();
        }

        // skip records
        $i = 0;
        while ($i < $position && $reader->next($this->iterationTag[0])) {
            $i++;
        }

        $j = 0;
        $records = array();
        while ($j < $count && $reader->next($this->iterationTag[0])) {
            $node = $reader->expand();
            $records[] = $this->toArrayTree($node, $this->iterationPath[0]);
            $j++;
        }

        return $records;
    }

    public function readFooter($fileName)
    {
        
    }

    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    protected function findIterationNode($node, array $path)
    {
        $path[] = $node["name"];
        foreach ($node['children'] as $child) {
            if ($child['type'] == 'iteration') {
                $this->iterationPath[] = implode('/', $path);
                $this->iterationTag[] = $child['name'];
            }

            $this->findIterationNode($child, $path);
        }
    }

    public function getTotalCount($fileName)
    {
        $z = new \XMLReader();
        $z->open($fileName);

        foreach (explode('/', $this->iterationPath[0]) as $node) {
            $z->next($node);
            $z->read();
        }

        $count = 0;
        while ($z->next($this->iterationTag[0])) {
            $count++;
        }

        return $count;
    }

}
