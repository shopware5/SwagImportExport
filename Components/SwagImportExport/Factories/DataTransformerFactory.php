<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Transoformers\PhpExpressionEvaluator;
use Shopware\Components\SwagImportExport\Transoformers\SmartyExpressionEvaluator;

class DataTransformerFactory extends \Enlight_Class implements \Enlight_Hook
{

    public function getTransformer($param)
    {
        switch ($param) {
            case 'phpEvaluator':
                return new PhpExpressionEvaluator();
            case 'smartyEvaluator':
                return new SmartyExpressionEvaluator();
            default: 
                throw new \Exception("Transformer $param is not valid");
        }
    }

}
