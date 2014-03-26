<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{

    private $cache;

    public function getAdapter($params)
    {
        $adapter = $this->cache[$params['adapter']];

        if ($adapter === null) {
            $adapter = $this->createDataAdapter($params);
        }
    }

    private function createDataAdapter($params)
    {
        $dbAdapter = $this->createDbAdapter();
        $colOpts = $this->createColOpts();
        $limit = $this->createLimit();
        $filter = $this->createFilter();

        return new DataDbAdapter($dbAdapter, $colOpts, $limit, $filter);
    }

    private function createDbAdapter($adapterType)
    {
        switch ($adapterType) {
            case 'categories':
                return $this->createCategoriesDbAdapter();
            default: throw Exception('Db adapter type is not valid');
        }
    }
    

    /**
     * This method can be hookable
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter
     */    
    public function createCategoriesDbAdapter()
    {
        return new CategoriesDbAdapter();
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter
     */
    public function createArticlesDbAdapter()
    {
        return new ArticlesDbAdapter();
    }

}
