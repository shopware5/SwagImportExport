<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Transoformers\PhpExpressionEvaluator;
use Shopware\Components\SwagImportExport\Transoformers\SmartyExpressionEvaluator;
use Shopware\Components\SwagImportExport\Transoformers\DataTransformerChain;
use Shopware\Components\SwagImportExport\Transoformers\TreeTransformer;
use Shopware\Components\SwagImportExport\Transoformers\ValuesTransformer;

class DataTransformerFactory extends \Enlight_Class implements \Enlight_Hook
{

    /**
     * Creates a data transformer chain by consuming data found a profile.
     * The $dataUserOptions is an object that will return info for the output file structure - tree or flat.
     */
    public function createDataTransformerChain($profile, $dataUserOptions)
    {
        // this can be put in a separate hookable function
        $dataTransformerChain = new DataTransformerChain();

        // for every config we create a transformer and add it to the chain
        $names = $profile->getConfigNames();
        
        foreach ($names as $name) {
            $config = $profile->getConfig($name);
            $transformer = $this->createDataTransformer($name, $config);
            $dataTransformerChain->add($transformer);
        }

        // a little hack: if we are in csv, we flatten the tree by adding a flattener at the end
        if (!$dataUserOptions['isTree']) {
            $transformer = $this->createDataTransformer('flatten', $profile->getConfig('tree'));
            $dataTransformerChain->add($transformer);
        }

        return $dataTransformerChain;
    }

    /**
     * Creates a concrete data transformer due to the given type - "values", "tree", "flatten"
     */
    public function createDataTransformer($transformerType, $config)
    {
        switch ($transformerType) {
            case 'tree':
                $transformer = new TreeTransformer();
                break;
            case 'exportConversion':
                $transformer = new ValuesTransformer();
                break;
            default:
                throw new \Exception("Transformer $transformerType is not valid");
        }
        
        $transformer->initialize($config);
        
        return $transformer;
    }

    public function createValueConvertor($convertorType)
    {
        switch ($convertorType) {
            case 'phpEvaluator':
                return new PhpExpressionEvaluator();
            case 'smartyEvaluator':
                return new SmartyExpressionEvaluator();
            default:
                throw new \Exception("Transformer $convertorType is not valid");
        }
    }

}
