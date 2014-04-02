<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

class SmartyExpressionEvaluator implements ExpressionEvaluator
{

    public function evaluate($expression, $variables)
    {
        if (empty($expression) || $expression == '') {
            return;
        }
        
        if ($variables === null) {
            throw new \Exception('Invalid variables passed to smarty evaluator');
        }
        
        $view = Shopware()->Template();
        $compiler = new \Shopware_Components_StringCompiler($view);
        
        $evaledParam = $compiler->compileSmartyString($expression, $variables);
        
        return trim($evaledParam);
    }
}
