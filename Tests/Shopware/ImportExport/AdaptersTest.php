<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DataAdapters\CategoriesAdapter;
use Shopware\Components\SwagImportExport\DataAdapters\ArticlesAdapter;

class AdaptersTests extends ImportExportTestHelper
{

    public function testAdapters()
    {
        //tests categories data adapter
        $catergoriesAdapter = $this->Plugin()->getDataAdapter('categories');
        $this->assertTrue($catergoriesAdapter instanceof CategoriesAdapter, 'Is not a instance of CategoriesAdapter');

        //tests categories data adapter
        $articlesAdapter = $this->Plugin()->getDataAdapter('articles');
        $this->assertTrue($articlesAdapter instanceof ArticlesAdapter, 'Is not a instance of ArticlesAdapter');
    }

}
