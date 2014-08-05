<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

class SmartyExpressionEvaluator implements ExpressionEvaluator
{

    /**
     * Shopware_Components_StringCompiler
     */
    protected $compiler;

    public function evaluate($expression, $variables)
    {
        if (empty($expression)) {
            throw new \Exception('Empty expression in smarty evaluator');
        }

        if ($variables === null) {
            throw new \Exception('Invalid variables passed to smarty evaluator');
        }

        $compiler = $this->getCompiler();

        $evaledParam = $compiler->compileSmartyString($expression, $variables);

        return trim($evaledParam);
    }

    /**
     * Returns compiler
     * 
     * @return Shopware_Components_StringCompiler
     */
    protected function getCompiler()
    {
        if ($this->compiler === null) {
            $view = Shopware()->Template();
            $this->compiler = new \Shopware_Components_StringCompiler($view);
        }

        return $this->compiler;
    }

}
