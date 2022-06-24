<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use SwagImportExport\Components\Profile\Profile;
use SwagImportExport\Components\Transformers\DataTransformerAdapter;
use SwagImportExport\Components\Transformers\DataTransformerChain;

class DataTransformerFactory implements \Enlight_Hook
{
    /**
     * @var iterable<DataTransformerAdapter>
     */
    private iterable $transformers;

    /**
     * @param iterable<DataTransformerAdapter> $transformers
     */
    public function __construct(
        iterable $transformers
    ) {
        $this->transformers = $transformers;
    }

    /**
     * Creates a data transformer chain by consuming data found a profile.
     * The $dataUserOptions is an object that will return info for the output file structure - tree or flat.
     *
     * @param array<string, bool> $dataUserOptions
     */
    public function createDataTransformerChain(Profile $profile, array $dataUserOptions): DataTransformerChain
    {
        // this can be put in a separate hookable function
        $dataTransformerChain = new DataTransformerChain();

        // for every config we create a transformer and add it to the chain
        $names = $profile->getConfigNames();

        foreach ($names as $name) {
            $transformer = $this->createDataTransformer($name, $profile);
            $dataTransformerChain->add($transformer);
        }

        // a little hack: if we are in csv, we flatten the tree by adding a flattener at the end
        if (!$dataUserOptions['isTree']) {
            $transformer = $this->createDataTransformer('flatten', $profile);
            $dataTransformerChain->add($transformer);
        }

        return $dataTransformerChain;
    }

    /**
     * Creates a concrete data transformer due to the given type - "values", "tree", "flatten"
     *
     * @throws \Exception
     */
    public function createDataTransformer(string $transformerType, Profile $profile): DataTransformerAdapter
    {
        $fittingTransformer = null;

        foreach ($this->transformers as $transformer) {
            if (!$transformer->supports($transformerType)) {
                continue;
            }

            $fittingTransformer = $transformer;
            break;
        }

        if (!$fittingTransformer instanceof DataTransformerAdapter) {
            throw new \Exception(sprintf('Transformer %s is not valid', $transformerType));
        }

        $fittingTransformer->initialize($profile);

        return $fittingTransformer;
    }
}
