<?php

namespace Shopware\Components\SwagImportExport\Factories;

use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\Session\Session;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\CustomModels\ImportExport\Session as SessionEntity;
use Shopware\CustomModels\ImportExport\Logger as LoggerEntity;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{
    private $cache;
    
    private $sessionRepository;

    /**
     * @param string $adapterType
     * @param array $postData
     * @return \Shopware\Components\SwagImportExport\DataIO
     */
    public function getAdapter($adapterType, $postData)
    {
        $adapter = $this->cache[$adapterType];

        if ($adapter === null) {
            $adapter = $this->createDataIO($adapterType, $postData);
        }
        
        return $adapter;
    }

    /**
     * @param $dbAdapter
     * @param $dataSession
     * @param $logger
     * @return DataIO
     */
    public function createDataIO($dbAdapter, $dataSession, $logger)
    {
        return new DataIO($dbAdapter, $dataSession, $logger);
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
        $event = Shopware()->Events()->notifyUntil(
                'Shopware_Components_SwagImportExport_Factories_CreateDbAdapter',
                array('subject' => $this, 'adapterType' => $adapterType)
        );

        if ($event && $event instanceof \Enlight_Event_EventArgs
                && $event->getReturn() instanceof DataDbAdapter) {
            return $event->getReturn();
        }

        switch ($adapterType) {
            case 'categories':
                return $this->createCategoriesDbAdapter();
            case 'articles':
                return $this->createArticlesDbAdapter();
            case 'articlesInStock':
                return $this->createArticlesInStockDbAdapter();
            case 'articlesTranslations':
                return $this->createArticlesTranslationsDbAdapter();
            case 'articlesPrices':
                return $this->createArticlesPricesDbAdapter();
            case 'articlesImages':
                return $this->createArticlesImagesDbAdapter();
            case 'orders':
                return $this->createOrdersDbAdapter();
            case 'mainOrders':
                return $this->createMainOrdersDbAdapter();
            case 'customers':
                return $this->createCustomerDbAdapter();
            case 'newsletter':
                return $this->createNewsletterDbAdapter();
            case 'translations':
                return $this->createTranslationsDbAdapter();
            default: throw new \Exception('Db adapter type is not valid');
        }
    }

    /**
     * Return necessary data manager
     *
     * @param string $managerType
     * @return object dbAdapter
     */
    public function createDataManager($managerType)
    {
        switch ($managerType) {
            case 'categories':
                return $this->getCategoryDataManager();
            case 'articles':
                return $this->getArticleDataManager();
            case 'customers':
                return $this->getCustomerDataManager();
            case 'newsletter':
                return $this->getNewsletterDataManager();
        }
    }

    public function loadSession($data)
    {
        $sessionId = $data['sessionId'];

        $sessionEntity = $this->getSessionRepository()->findOneBy(array('id' => $sessionId));

        if (!$sessionEntity) {
            $sessionEntity = new SessionEntity();
            $loggerEntity = new LoggerEntity();
            $loggerEntity->setCreatedAt();

            $sessionEntity->setLogger($loggerEntity);
        }

        $session = $this->createSession($sessionEntity);

        return $session;
    }

    public function loadLogger(Session $session, $fileWriter)
    {
        $logger = $this->createLogger($session, $fileWriter);

        return $logger;
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
    public function createLimit(array $limit)
    {
        return new DataLimit($limit);
    }
    
    /**
     * Returns filter adapter
     * 
     * @param $filter
     * @return \Shopware\Components\SwagImportExport\Utils\DataFilter
     */
    public function createFilter($filter)
    {
        return new DataFilter($filter);
    }
    
    /**
     * Helper Method to get access to the session repository.
     *
     * @return \Shopware\CustomModels\ImportExport\Session
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

    protected function createLogger($session, $fileWriter)
    {
        return new Logger($session, $fileWriter);
    }

    /**
     * This method can be hookable
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter
     */
    protected function createCategoriesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter
     */
    protected function createArticlesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter
     */
    protected function createArticlesInStockDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesTranslationsDbAdapter
     */
    protected function createArticlesTranslationsDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\ArticlesTranslationsDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * This method can be hookable
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesPricesDbAdapter
     */
    protected function createArticlesPricesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\ArticlesPricesDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * This method can be hookable
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter
     */
    protected function createArticlesImagesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter
     */
    protected function createCustomerDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\OrdersDbAdapter
     */
    protected function createOrdersDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\OrdersDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * @return \Shopware\Components\SwagImportExport\DbAdapters\MainOrdersDbAdapter
     */
    protected function createMainOrdersDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\MainOrdersDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * 
     * @return \Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter
     */
    protected function createNewsletterDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
                ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter');
        return new $proxyAdapter;
    }
    /**
     *
     * @return \Shopware\Components\SwagImportExport\DbAdapters\TranslationsDbAdapter
     */
    protected function createTranslationsDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DbAdapters\TranslationsDbAdapter');
        return new $proxyAdapter;
    }

    /**
     * @return \Shopware\Components\SwagImportExport\DataManagers\CategoriesDataManager
     */
    protected function getCategoryDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\CategoriesDataManager');
        return new $proxyAdapter;
    }

    /**
     * @return \Shopware\Components\SwagImportExport\DataManagers\Articles\ArticleDataManager
     */
    protected function getArticleDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\Articles\ArticleDataManager');
        return new $proxyAdapter;
    }

    /**
     * @return \Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager
     */
    protected function getCustomerDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager');
        return new $proxyAdapter;
    }

    /**
     * @return \Shopware\Components\SwagImportExport\DataManagers\NewsletterDataManager
     */
    protected function getNewsletterDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\NewsletterDataManager');
        return new $proxyAdapter;
    }
}
