<?php

declare(strict_types=1);
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
     * @var array<DataTransformerAdapter>
     */
    private array $chain = [];

    /**
     * Installs a new transformer in the chain.
     */
    public function add(DataTransformerAdapter $transformer): void
    {
        $this->chain[] = $transformer;
    }

    /**
     * Execute the transformers in the way they were installed
     */
    public function transformForward(array $data): array
    {
        foreach ($this->chain as $transformer) {
            $data = $transformer->transformForward($data);
        }

        return $data;
    }

    /**
     * Execute the transformers back in the web they were installed
     */
    public function transformBackward(array $data): array
    {
        foreach (\array_reverse($this->chain) as $transformer) {
            $data = $transformer->transformBackward($data);
        }

        return $data;
    }

    /**
     * Compose the headers by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     */
    public function composeHeader(): array
    {
        return $this->getLastComposerTransformer($this->chain)->composeHeader();
    }

    /**
     * Compose the footers by using the last transformer in the chain.
     * (Always last because that is the closest transformer to the export/import physical file)
     */
    public function composeFooter(): array
    {
        return $this->getLastComposerTransformer($this->chain)->composeFooter();
    }

    /**
     * Returns the last transformer implementing the composer-interface.
     *
     * @throws \Exception
     */
    private function getLastComposerTransformer(array $transformers): ComposerInterface
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
