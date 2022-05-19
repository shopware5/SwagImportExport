<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use SwagImportExport\Components\Transformers\DataTransformerChain;
use SwagImportExport\Components\Transformers\DecimalTransformer;
use SwagImportExport\Components\Transformers\FlattenTransformer;
use SwagImportExport\Components\Transformers\PhpExpressionEvaluator;
use SwagImportExport\Components\Transformers\SmartyExpressionEvaluator;
use SwagImportExport\Components\Transformers\TreeTransformer;
use SwagImportExport\Components\Transformers\ValuesTransformer;

class DataTransformerFactory extends \Enlight_Class implements \Enlight_Hook
{
    /**
     * Creates a data transformer chain by consuming data found a profile.
     * The $dataUserOptions is an object that will return info for the output file structure - tree or flat.
     *
     * @param \SwagImportExport\Components\Profile\Profile $profile
     * @param array                                        $dataUserOptions
     *
     * @return \SwagImportExport\Components\Transformers\DataTransformerChain
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
     *
     * @throws \Exception
     *
     * @return FlattenTransformer|TreeTransformer|ValuesTransformer
     */
    public function createDataTransformer($transformerType, $config)
    {
        switch ($transformerType) {
            case 'tree':
                $transformer = new TreeTransformer();
                break;
            case 'exportConversion':
                $config = [
                    'expression' => $config,
                    'evaluator' => $this->createValueConvertor('smartyEvaluator'),
                ];
                $transformer = new ValuesTransformer();
                break;
            case 'flatten':
                $transformer = new FlattenTransformer();
                break;
            case 'decimals':
                $transformer = new DecimalTransformer();
                break;
            default:
                throw new \Exception("Transformer $transformerType is not valid");
        }

        $transformer->initialize($config);

        return $transformer;
    }

    /**
     * @throws \Exception
     *
     * @return PhpExpressionEvaluator|SmartyExpressionEvaluator
     */
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
