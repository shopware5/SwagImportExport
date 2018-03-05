<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shopware\Components\SwagImportExport\Factories;

use Doctrine\ORM\EntityRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\SwagImportExport\DataIO;
use Shopware\Components\SwagImportExport\DataManagers\Articles\ArticleDataManager;
use Shopware\Components\SwagImportExport\DataManagers\CategoriesDataManager;
use Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager;
use Shopware\Components\SwagImportExport\DataManagers\NewsletterDataManager;
use Shopware\Components\SwagImportExport\DbAdapters\AddressDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesImagesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesInStockDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesPricesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\ArticlesTranslationsDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CategoriesDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CustomerCompleteDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\CustomerDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\DataDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\MainOrdersDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\NewsletterDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\OrdersDbAdapter;
use Shopware\Components\SwagImportExport\DbAdapters\TranslationsDbAdapter;
use Shopware\Components\SwagImportExport\DbalHelper;
use Shopware\Components\SwagImportExport\Session\Session;
use Shopware\Components\SwagImportExport\Logger\Logger;
use Shopware\Components\SwagImportExport\Utils\DataColumnOptions;
use Shopware\Components\SwagImportExport\Utils\DataLimit;
use Shopware\Components\SwagImportExport\Utils\DataFilter;
use Shopware\Components\SwagImportExport\Validators\AddressValidator;
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
        $uploadPathProvider = Shopware()->Container()->get('swag_import_export.upload_path_provider');
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
        $event = $this->fireCreateFactoryEvent($adapterType);
        if ($event && $event instanceof \Enlight_Event_EventArgs
            && $event->getReturn() instanceof DataDbAdapter
        ) {
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
            case DataDbAdapter::CUSTOMER_COMPLETE_ADAPTER:
                return $this->createCustomerCompleteDbAdapter();
            case DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER:
                return $this->createNewsletterDbAdapter();
            case DataDbAdapter::TRANSLATION_ADAPTER:
                return $this->createTranslationsDbAdapter();
            case DataDbAdapter::ADDRESS_ADAPTER:
                return $this->createAddressDbAdapter();
            default:
                throw new \Exception('Db adapter type is not valid');
        }
    }

    /**
     * @param string $adapterType
     * @return \Enlight_Event_EventArgs|null
     */
    protected function fireCreateFactoryEvent($adapterType)
    {
        return Shopware()->Events()->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateDbAdapter',
            ['subject' => $this, 'adapterType' => $adapterType]);
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
     * @return CategoriesDataManager
     */
    protected function getCategoryDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\CategoriesDataManager');
        return new $proxyAdapter;
    }

    /**
     * @return ArticleDataManager
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
     * @return CustomerDataManager
     */
    protected function getCustomerDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\CustomerDataManager');
        return new $proxyAdapter;
    }

    /**
     * @return NewsletterDataManager
     */
    protected function getNewsletterDataManager()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy('Shopware\Components\SwagImportExport\DataManagers\NewsletterDataManager');
        return new $proxyAdapter;
    }

    /**
     * This method can be hookable
     *
     * @return CategoriesDbAdapter
     */
    protected function createCategoriesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(CategoriesDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     *
     * @return ArticlesDbAdapter
     */
    protected function createArticlesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(ArticlesDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return ArticlesInStockDbAdapter
     */
    protected function createArticlesInStockDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(ArticlesInStockDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     *
     * @return ArticlesTranslationsDbAdapter
     */
    protected function createArticlesTranslationsDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(ArticlesTranslationsDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * This method can be hookable
     *
     * @return ArticlesPricesDbAdapter
     */
    protected function createArticlesPricesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(ArticlesPricesDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * This method can be hookable
     *
     * @return ArticlesImagesDbAdapter
     */
    protected function createArticlesImagesDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(ArticlesImagesDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return CustomerDbAdapter
     */
    protected function createCustomerDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(CustomerDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return CustomerCompleteDbAdapter
     */
    protected function createCustomerCompleteDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(CustomerCompleteDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return OrdersDbAdapter
     */
    protected function createOrdersDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(OrdersDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return MainOrdersDbAdapter
     */
    protected function createMainOrdersDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(MainOrdersDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return NewsletterDbAdapter
     */
    protected function createNewsletterDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(NewsletterDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return TranslationsDbAdapter
     */
    protected function createTranslationsDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(TranslationsDbAdapter::class);
        return new $proxyAdapter;
    }

    /**
     * @return AddressDbAdapter
     */
    private function createAddressDbAdapter()
    {
        $proxyAdapter = Shopware()->Hooks()
            ->getProxy(AddressDbAdapter::class);
        return new $proxyAdapter;
    }
}
