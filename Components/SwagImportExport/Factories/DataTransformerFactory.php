<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\Transoformers\PhpExpressionEvaluator;

class DataTransformerFactory extends \Enlight_Class implements \Enlight_Hook
{

    public function getTransformer($param)
    {
        switch ($param) {
            case 'phpEvaluator':
                return new PhpExpressionEvaluator();
            default: 
                throw new \Exception("Transformer $param is not valid");
        }
    }

}
