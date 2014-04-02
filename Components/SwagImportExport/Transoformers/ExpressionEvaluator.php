<?php

namespace Shopware\Components\SwagImportExport\Transoformers;

interface ExpressionEvaluator
{

    public function evaluate($expression, $variables);
}
