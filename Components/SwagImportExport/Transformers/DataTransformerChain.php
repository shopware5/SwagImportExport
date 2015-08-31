<?php

namespace Shopware\Components\SwagImportExport\Transformers;

/**
 * The responsibility of this class is to execute all the transformers in a predefined order
 * and to pass the output of one as input to the next.
 */
class DataTransformerChain
{
    /**
     * @var array DataTransformerAdapter[]
     */
    private $chain = array();

    /**
     * Installs a new transformer in the chain.
     *
     * @param DataTransformerAdapter $transformer
     */
    public function add(DataTransformerAdapter $transformer)
    {
        $this->chain[] = $transformer;
    }

    /**
     * Execute the transformers in the way they were installed
     *
     * @param $data
     * @return
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
     * @param $data
     * @return
     */
    public function transformBackward($data)
    {
        /** @var DataTransformerAdapter $transformer */
        foreach (array_reverse($this->chain) as $transformer) {
            $data = $transformer->transformBackward($data);
        }

        return $data;
    }

    /**
     * Compose the headers by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @return mixed
     */
    public function composeHeader()
    {
        $transformer = end($this->chain);

        return $transformer->composeHeader();
    }

    /**
     * Compose the footers by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @return mixed
     */
    public function composeFooter()
    {
        $transformer = end($this->chain);

        return $transformer->composeFooter();
    }

    /**
     * Parse the header data by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @param $data
     * @return mixed
     */
    public function parseHeader($data)
    {
        $transformer = end($this->chain);

        return $transformer->parseHeader($data);
    }

    /**
     * Parse the footer data by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     *
     * @param $data
     * @return mixed
     */
    public function parseFooter($data)
    {
        $transformer = end($this->chain);

        return $transformer->parseFooter($data);
    }
}
