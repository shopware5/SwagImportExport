<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

/**
 * The responsibility of this class is to execute all the transformers in a predefined order
 * and to pass the output of one as input to the next.
 */
class DataTransformerChain
{
    /**
     * @var array DataTransformerAdapter[]
     */
    private array $chain = [];

    /**
     * Installs a new transformer in the chain.
     */
    public function add(DataTransformerAdapter $transformer)
    {
        $this->chain[] = $transformer;
    }

    /**
     * Execute the transformers in the way they were installed
     *
     * @return array
     */
    public function transformForward($data)
    {
        /** @var DataTransformerAdapter $transformer */
        foreach ($this->chain as $transformer) {
            $data = $transformer->transformForward($data);
        }

        return $data;
    }

    /**
     * Execute the transformers back in the web they were installed
     *
     * @return array
     */
    public function transformBackward($data)
    {
        /** @var DataTransformerAdapter $transformer */
        foreach (\array_reverse($this->chain) as $transformer) {
            $data = $transformer->transformBackward($data);
        }

        return $data;
    }

    /**
     * Compose the headers by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @return array
     */
    public function composeHeader()
    {
        $transformer = $this->getLastComposerTransformer($this->chain);

        return $transformer->composeHeader();
    }

    /**
     * Compose the footers by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @return array
     */
    public function composeFooter()
    {
        $transformer = $this->getLastComposerTransformer($this->chain);

        return $transformer->composeFooter();
    }

    /**
     * Parse the header data by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @param array $data
     *
     * @return array
     */
    public function parseHeader($data)
    {
        $transformer = $this->getLastComposerTransformer($this->chain);

        return $transformer->parseHeader($data);
    }

    /**
     * Parse the footer data by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @param array $data
     *
     * @return array
     */
    public function parseFooter($data)
    {
        $transformer = $this->getLastComposerTransformer($this->chain);

        return $transformer->parseFooter($data);
    }

    /**
     * Returns the last transformer implementing the composer-interface.
     *
     * @param array $transformers
     *
     * @throws \Exception
     *
     * @return ComposerInterface
     */
    private function getLastComposerTransformer($transformers)
    {
        $transformers = \array_reverse($transformers);
        foreach ($transformers as $transformer) {
            if ($transformer instanceof ComposerInterface) {
                return $transformer;
            }
        }

        throw new \Exception('No composer transformer found.');
    }
}
