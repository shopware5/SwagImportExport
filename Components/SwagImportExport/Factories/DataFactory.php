<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\CustomModels\ImportExport\Session;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{

    private $cache;
    
    private $sessionRepository;

    /**
     * @param string $adapterType
     * @param array $postData
     * @return Shopware\Components\SwagImportExport\DataIO
     */
    public function getAdapter($adapterType,$postData)
    {
        $adapter = $this->cache[$adapterType];

        if ($adapter === null) {
            $adapter = $this->createDataIO($adapterType, $postData);
        }
        
        return $adapter;
    }

    /**
     * @param array $params
     * @return \Shopware\Components\SwagImportExport\DataIO
     */
    public function createDataIO($dbAdapter, $dataSession)
    {
        return new DataIO($dbAdapter, $dataSession);
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
    
    public function loadSession($data)
    {   
        $sessionId = $data['sessionId'];

        $session = $this->getSessionRepository()->findOneBy(array('id' => $sessionId));

        if (!$session) {
            $session = $this->createSession();
        }

        return $session;
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
     * Helper Method to get access to the session repository.
     *
     * @return Shopware\CustomModels\ImportExport\Session
     */
    public function getSessionRepository()
    {
        if ($this->sessionRepository === null) {
            $this->sessionRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\ImportExport\Session');
        }
        return $this->sessionRepository;
    }
    
    protected function createSession()
    {
        return new Session();
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
