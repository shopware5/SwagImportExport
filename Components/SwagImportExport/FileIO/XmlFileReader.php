<?php

namespace Shopware\Components\SwagImportExport\FileIO;

class XmlFileReader implements FileReader
{

    protected $tree;
    protected $iterationPath;
    protected $iterationTag;
    private $treeStructure = true;

    public function __construct()
    {
        
    }

    public function setTree($tree)
    {
        $this->tree = $tree;
        $this->findIterationNode($tree, array());
    }

    protected function toArrayTree(\DOMElement $node)
    {
        $hasChildren = false;
        $record = array();

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $hasChildren = true;
                    $record[$child->nodeName] = $this->toArrayTree($child);
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
        $z = new \XMLReader();
        $z->open($fileName);

        foreach ($this->iterationPath as $node) {
            $z->next($node);
            $z->read();
        }

        // skip records
        $i = 0;
        while ($i < $position && $z->next($this->iterationTag)) {
            $i++;
        }

        $records = array();
        while ($i < $count && $z->next($this->iterationTag)) {
            $node = $z->expand();
            $records[] = $this->toArrayTree($node);
            $i++;
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
            if ($child['type'] == 'record') {
                $this->iterationPath = $path;
                $this->iterationTag = $child['name'];
                return;
            }

            $this->findIterationNode($child, $path);
        }
    }

    public function getTotalCount($fileName)
    {
        $z = new \XMLReader();
        $z->open($fileName);

        foreach ($this->iterationPath as $node) {
            $z->next($node);
            $z->read();
        }

        $count = 0;
        while ($z->next($this->iterationTag)) {
            $count++;
        }

        return $count;
    }

}
