<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DataAdapters\CategoriesAdapter;

class AdaptersTests extends ImportExportTestHelper
{

    public function testAdapters()
    {
        //get data scope
        $catergoriesAdapter = $this->Plugin()->getDataAdapter('categories');
        
        $this->assertTrue($catergoriesAdapter instanceof CategoriesAdapter, 'catergoriesAdapter is not a instance of CategoriesAdapter');
    }

}
