<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

class PhpExpressionEvaluator implements ExpressionEvaluator
{

    public function evaluate($expression, $variables)
    {
        if (empty($expression) || $expression == '') {
            return;
        }
        
        if ($variables === null) {
            throw new \Exception('Invalid variables passed to php evaluator');
        }
        
        extract($variables);
        
        $errorBefore = error_get_last();
        
        $evaledParam = @eval("return " . $expression . ";");
        
        $errorAfter = error_get_last();
        			
        if($errorAfter && ($errorBefore != $errorAfter))
        {
            throw new \Exception("Error on evaluating  with expression $expression. Error message: {$errorAfter['message']}");
        }
        
        return $evaledParam;
    }

}
