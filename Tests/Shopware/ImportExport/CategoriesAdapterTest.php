<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DataAdapters\CategoriesAdapter;

class CategoriesAdapterTest extends ImportExportTestHelper
{

    public function testExport()
    {
        $categoriesAdapter = $this->Plugin()->getDataAdapter('categories');
        
        $rawData = $categoriesAdapter->extract();
        
        $this->assertEquals(count($rawData), 62);
    }
    
    public function testExportLimit()
    {
        $categoriesAdapter = $this->Plugin()->getDataAdapter('categories');
        
        //sets offset and limit
        $limitAdapter = $this->Plugin()->getDataAdapter('limit');
        $limitAdapter->setOffset(0);
        $limitAdapter->setLimit(20);
        
        $categoriesAdapter->setDataAdapterLimit($limitAdapter);
        
        $rawData = $categoriesAdapter->extract();
        
        $this->assertEquals(count($rawData), 20);
    }

}
