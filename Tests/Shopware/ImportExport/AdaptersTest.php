<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;

class AdaptersTests extends ImportExportTestHelper
{

    public function testAdapters()
    {
        $dataFactory = $this->Plugin()->getDataFactory();
        
        //tests categories data adapter
        $catergoriesDbAdapter = $dataFactory->createCategoriesDbAdapter();
        $this->assertTrue($catergoriesDbAdapter instanceof CategoriesDbAdapter, 'Is not a instance of CategoriesDbAdapter');
        
        //tests articles data adapter
        $articlesDbAdapter = $dataFactory->createArticlesDbAdapter();
        $this->assertTrue($articlesDbAdapter instanceof ArticlesDbAdapter, 'Is not a instance of ArticlesDbAdapter');
        
    }

}
