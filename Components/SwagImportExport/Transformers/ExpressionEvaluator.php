<?php

namespace Shopware\Components\SwagImportExport\Transformers;

interface ExpressionEvaluator
{
    /**
     * @param $expression
     * @param $variables
     * @return mixed
     */
    public function evaluate($expression, $variables);
}
