<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

class SmartyExpressionEvaluator implements ExpressionEvaluator
{
    protected ?\Shopware_Components_StringCompiler $compiler = null;

    /**
     * @throws \Exception
     */
    public function evaluate(string $expression, ?array $variables): string
    {
        if (empty($expression)) {
            throw new \Exception('Empty expression in smarty evaluator');
        }

        if ($variables === null) {
            throw new \Exception('Invalid variables passed to smarty evaluator');
        }

        $compiler = $this->getCompiler();

        $this->convertPricesColumnsToFloat($variables);

        $evaledParam = $compiler->compileSmartyString($expression, $variables);

        return \trim($evaledParam);
    }

    /**
     * Returns compiler
     */
    protected function getCompiler(): \Shopware_Components_StringCompiler
    {
        if ($this->compiler === null) {
            $view = Shopware()->Template();
            $this->compiler = new \Shopware_Components_StringCompiler($view);
        }

        return $this->compiler;
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function convertPricesColumnsToFloat(array &$variables): void
    {
        if (isset($variables['price'])) {
            $variables['price'] = (float) \str_replace(',', '.', $variables['price']);
        }
        if (isset($variables['pseudoPrice'])) {
            $variables['pseudoPrice'] = (float) \str_replace(',', '.', $variables['pseudoPrice']);
        }
        if (isset($variables['purchasePrice'])) {
            $variables['purchasePrice'] = (float) \str_replace(',', '.', $variables['purchasePrice']);
        }
        if (isset($variables['regulationPrice'])) {
            $variables['regulationPrice'] = (float) \str_replace(',', '.', $variables['regulationPrice']);
        }
    }
}
