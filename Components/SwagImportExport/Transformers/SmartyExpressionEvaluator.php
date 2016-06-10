<?php

namespace Shopware\Components\SwagImportExport\Transformers;

class SmartyExpressionEvaluator implements ExpressionEvaluator
{
    /**
     * Shopware_Components_StringCompiler
     */
    protected $compiler;

    /**
     * @param $expression
     * @param $variables
     * @return string
     * @throws \Exception
     */
    public function evaluate($expression, $variables)
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

        return trim($evaledParam);
    }

    /**
     * Returns compiler
     *
     * @return \Shopware_Components_StringCompiler
     */
    protected function getCompiler()
    {
        if ($this->compiler === null) {
            $view = Shopware()->Template();
            $this->compiler = new \Shopware_Components_StringCompiler($view);
        }

        return $this->compiler;
    }

    /**
     * @param array $variables
     */
    protected function convertPricesColumnsToFloat(&$variables)
    {
        if (isset($variables['price'])) {
            $variables['price'] = (float) str_replace(',', '.', $variables['price']);
        }
        if (isset($variables['pseudoPrice'])) {
            $variables['pseudoPrice'] = (float) str_replace(',', '.', $variables['pseudoPrice']);
        }
    }
}
