<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\FileIO;

use Shopware\Components\SwagImportExport\Utils\FileHelper;

class XmlFileReader implements FileReader
{
    protected $tree;
    protected $iterationPath = array();
    protected $iterationTag = array();

    /**
     * @var bool $treeStructure
     */
    protected $treeStructure = true;

    /**
     * @var FileHelper $fileHelper
     */
    protected $fileHelper;

    /**
     * @param FileHelper $fileHelper
     */
    public function __construct(FileHelper $fileHelper = null)
    {
        $this->fileHelper = $fileHelper;
    }

    /**
     * @param $tree
     */
    public function setTree($tree)
    {
        $this->tree = $tree;
        $this->findIterationNode($tree, array());
    }

    /**
     * @param \DOMElement $node
     * @param $path
     * @return array|string
     */
    protected function toArrayTree(\DOMElement $node, $path)
    {
        $hasChildren = false;
        $record = array();
        $currentPath = $path . '/' . $node->nodeName;

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $hasChildren = true;
                    if (in_array($currentPath, $this->iterationPath) && in_array($child->nodeName, $this->iterationTag)) {
                        $record[$child->nodeName][] = $this->toArrayTree($child, $currentPath);
                    } else {
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
        } elseif (!$hasChildren) {
            $record = trim($node->nodeValue);
        }

        return $record;
    }

    /**
     * @param $fileName
     */
    public function readHeader($fileName)
    {
    }

    /**
     * @param $fileName
     * @param $position
     * @param $count
     * @return array
     */
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

    /**
     * @param $fileName
     */
    public function readFooter($fileName)
    {
    }

    /**
     * @return bool
     */
    public function hasTreeStructure()
    {
        return $this->treeStructure;
    }

    /**
     * @param $node
     * @param array $path
     */
    protected function findIterationNode($node, array $path)
    {
        $path[] = $node["name"];
        if (isset($node['children'])) {
            foreach ($node['children'] as $child) {
                if ($child['type'] == 'iteration') {
                    $this->iterationPath[] = implode('/', $path);
                    $this->iterationTag[] = $child['name'];
                }

                $this->findIterationNode($child, $path);
            }
        }
    }

    /**
     * @param $fileName
     * @return int
     */
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
