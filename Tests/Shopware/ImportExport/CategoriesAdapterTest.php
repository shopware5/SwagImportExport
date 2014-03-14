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

}
