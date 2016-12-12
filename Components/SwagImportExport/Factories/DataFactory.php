<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Factories;

use Doctrine\ORM\EntityRepository;
use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Session\Session;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\CustomModels\ImportExport\Session as SessionEntity;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{
    /** @var EntityRepository */
    private $sessionRepository;

    /**
     * @param DataDbAdapter $dbAdapter
     * @param $dataSession
     * @param Logger $logger
     * @return DataIO
     */
    public function createDataIO(DataDbAdapter $dbAdapter, $dataSession, Logger $logger)
    {
        $uploadPathProvider =  Shopware()->Container()->get('swag_import_export.upload_path_provider');
        return new DataIO($dbAdapter, $dataSession, $logger, $uploadPathProvider);
    }

    /**
     * Returns the necessary adapter
     * 
     * @param string $adapterType
     * @return DataDbAdapter dbAdapter
     * @throws \Exception
     */
    public function createDbAdapter($adapterType)
    {
        $event = Shopware()->Events()->notifyUntil(
                'Shopware_Components_SwagImportExport_Factories_CreateDbAdapter',
                ['subject' => $this, 'adapterType' => $adapterType]
        );

        if ($event && $event instanceof \Enlight_Event_EventArgs
                && $event->getReturn() instanceof DataDbAdapter) {
            return $event->getReturn();
        }

        switch ($adapterType) {
            case DataDbAdapter::CATEGORIES_ADAPTER:
                return $this->createCategoriesDbAdapter();
            case DataDbAdapter::ARTICLE_ADAPTER:
                return $this->createArticlesDbAdapter();
            case DataDbAdapter::ARTICLE_INSTOCK_ADAPTER:
                return $this->createArticlesInStockDbAdapter();
            case DataDbAdapter::ARTICLE_TRANSLATION_ADAPTER:
                return $this->createArticlesTranslationsDbAdapter();
            case DataDbAdapter::ARTICLE_PRICE_ADAPTER:
                return $this->createArticlesPricesDbAdapter();
            case DataDbAdapter::ARTICLE_IMAGE_ADAPTER:
                return $this->createArticlesImagesDbAdapter();
            case DataDbAdapter::ORDER_ADAPTER:
                return $this->createOrdersDbAdapter();
            case DataDbAdapter::MAIN_ORDER_ADAPTER:
                return $this->createMainOrdersDbAdapter();
            case DataDbAdapter::CUSTOMER_ADAPTER:
                return $this->createCustomerDbAdapter();
            case DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER:
                return $this->createNewsletterDbAdapter();
            case DataDbAdapter::TRANSLATION_ADAPTER:
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
            case DataDbAdapter::CATEGORIES_ADAPTER:
                return $this->getCategoryDataManager();
            case DataDbAdapter::ARTICLE_ADAPTER:
                return $this->getArticleDataManager();
            case DataDbAdapter::CUSTOMER_ADAPTER:
                return $this->getCustomerDataManager();
            case DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER:
                return $this->getNewsletterDataManager();
        }
    }

    /**
     * @param array $data
     * @return Session
     */
    public function loadSession($data)
    {
        $sessionId = $data['sessionId'];

        $sessionEntity = $this->getSessionRepository()->findOneBy(['id' => $sessionId]);

        if (!$sessionEntity) {
            $sessionEntity = new SessionEntity();
        }

        return $this->createSession($sessionEntity);
    }

    /**
     * Returns columnOptions adapter
     * 
     * @param $options
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
     * @return EntityRepository
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
        $db = Shopware()->Db();
        $dbalHelper = DbalHelper::create();
        return new $proxyAdapter($db, $dbalHelper);
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
