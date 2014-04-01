<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{

    private $cache;

    /**
     * @param string $type
     * @param array $postData
     * @return Shopware\Components\SwagImportExport\DataIO
     */
    public function getAdapter($type,$postData)
    {
        $adapter = $this->cache[$type];

        if ($adapter === null) {
            $adapter = $this->createDataIO($type, $postData);
        }
        
        return $adapter;
    }

    /**
     * @param array $params
     * @return \Shopware\Components\SwagImportExport\DataIO
     */
    private function createDataIO($type, $params)
    {
        $dbAdapter = $this->createDbAdapter($type);
        $colOpts = $this->createColOpts($params['columnOptions']);
        $limit = $this->createLimit($params['limit']);
        $filter = $this->createFilter($params['filter']);

        return new DataIO($dbAdapter, $colOpts, $limit, $filter);
    }

    /**
     * Returns the necessary adapter
     * 
     * @param string $adapterType
     * @return object dbAdapter
     * @throws \Exception
     */
    public function createDbAdapter($adapterType)
    {
        switch ($adapterType) {
            case 'categories':
                return $this->createCategoriesDbAdapter();
            case 'articles':
                return $this->createArticlesDbAdapter();
            default: throw new \Exception('Db adapter type is not valid');
        }
    }
    
    /**
     * Returns columnOptions adapter
     * 
     * @param type $options
     * @return \Shopware\Components\SwagImportExport\Utils\DataColumnOptions
     */
    public function createColOpts($options)
    {
        return new DataColumnOptions($options);
    }
    
    /**
     * Returns limit adapter
     * 
     * @param array $limit
     * @return \Shopware\Components\SwagImportExport\Utils\DataLimit
     */
    public function createLimit($limit)
    {
        return new DataLimit($limit);
    }
    
    /**
     * Returns filter adapter
     * 
     * @param type $filter
     * @return \Shopware\Components\SwagImportExport\Utils\DataFilter
     */
    public function createFilter($filter)
    {
        return new DataFilter($filter);
    }

    /**
     * This method can be hookable
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter
     */    
    protected function createCategoriesDbAdapter()
    {
        return new CategoriesDbAdapter();
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter
     */
    protected function createArticlesDbAdapter()
    {
        return new ArticlesDbAdapter();
    }

}
