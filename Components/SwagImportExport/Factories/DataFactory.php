<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\Session\Session;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesPricesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\CustomModels\ImportExport\Session as SessionEntity;

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
            case 'articlesInStock':
                return $this->createArticlesInStockDbAdapter();
            case 'articlesPrices':
                return $this->createArticlesPricesDbAdapter();
            case 'customers':
                return $this->createCustomerDbAdapter();
            case 'newsletter':
                return $this->createNewsletterDbAdapter();
            default: throw new \Exception('Db adapter type is not valid');
        }
    }
    
    public function loadSession($data)
    {   
        $sessionId = $data['sessionId'];

        $sessionEntity = $this->getSessionRepository()->findOneBy(array('id' => $sessionId));

        if (!$sessionEntity) {
            $sessionEntity = new SessionEntity();
        }
        
        $session = $this->createSession($sessionEntity);

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
    
    protected function createSession(SessionEntity $sessionEntity)
    {
        return new Session($sessionEntity);
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
    
    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter
     */
    protected function createArticlesInStockDbAdapter()
    {
        return new ArticlesInStockDbAdapter();
    }

    /**
     * This method can be hookable
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesPricesDbAdapter
     */    
    protected function createArticlesPricesDbAdapter()
    {
        return new ArticlesPricesDbAdapter();
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter
     */
    protected function createCustomerDbAdapter()
    {
        return new CustomerDbAdapter();
    }
    
    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter
     */
    protected function createNewsletterDbAdapter()
    {
        return new NewsletterDbAdapter();
    }
    
    

}
