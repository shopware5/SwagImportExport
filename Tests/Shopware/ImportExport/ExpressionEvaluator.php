<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;

class ExpressionEvaluator extends ImportExportTestHelper
{
    public function testPhpEvaluator()
    {
    
        $variables = array(
            'title' => 'Product',
            'active' => true,
            'status' => 2,
            
        );
        
        $expression1 = '$active = $active ? false : true' ;
        $expression2 = '$title = $title . \'-Test\'' ;
        
        $transformersFactory = $this->Plugin()->getDataTransformerFactory();
        
        $phpEval = $transformersFactory->getTransformer('phpEvaluator');
        
        $evalVariable1 = $phpEval->evaluate($expression1, $variables);
        $evalVariable2 = $phpEval->evaluate($expression2, $variables);

        $this->assertEquals($evalVariable1, false);
        $this->assertEquals($evalVariable2, 'Product-Test');
        
    }
}
