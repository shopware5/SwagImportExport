<?php

namespace Tests\Shopware\ImportExport;

use Tests\Shopware\ImportExport\ImportExportTestHelper;
use Shopware\Components\SwagImportExport\DataAdapters\CategoriesAdapter;

class CategoriesAdapterTest extends ImportExportTestHelper
{

    public function testRawData()
    {
        $categoriesAdapter = $this->Plugin()->getDataAdapter('categories');

        $rawData = $categoriesAdapter->getRawData();

        $this->assertEquals(count($rawData), 62);
    }

    public function testRawDataLimit()
    {
        $categoriesAdapter = $this->Plugin()->getDataAdapter('categories');

        //sets offset and limit
        $limitAdapter = $this->Plugin()->getDataAdapter('limit');
        $limitAdapter->setOffset(0);
        $limitAdapter->setLimit(20);

        $categoriesAdapter->setDataAdapterLimit($limitAdapter);

        $rawData = $categoriesAdapter->getRawData();

        $this->assertEquals(count($rawData), 20);
    }

}
