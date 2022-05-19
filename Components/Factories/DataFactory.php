<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Factories;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use SwagImportExport\Components\DataIO;
use SwagImportExport\Components\DataManagers\Articles\ArticleDataManager;
use SwagImportExport\Components\DataManagers\CategoriesDataManager;
use SwagImportExport\Components\DataManagers\CustomerDataManager;
use SwagImportExport\Components\DataManagers\NewsletterDataManager;
use SwagImportExport\Components\DbAdapters\AddressDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesImagesDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesInStockDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesPricesDbAdapter;
use SwagImportExport\Components\DbAdapters\ArticlesTranslationsDbAdapter;
use SwagImportExport\Components\DbAdapters\CategoriesDbAdapter;
use SwagImportExport\Components\DbAdapters\CategoryTranslationDbAdapter;
use SwagImportExport\Components\DbAdapters\CustomerCompleteDbAdapter;
use SwagImportExport\Components\DbAdapters\CustomerDbAdapter;
use SwagImportExport\Components\DbAdapters\DataDbAdapter;
use SwagImportExport\Components\DbAdapters\MainOrdersDbAdapter;
use SwagImportExport\Components\DbAdapters\NewsletterDbAdapter;
use SwagImportExport\Components\DbAdapters\OrdersDbAdapter;
use SwagImportExport\Components\DbAdapters\TranslationsDbAdapter;
use SwagImportExport\Components\Logger\Logger;
use SwagImportExport\Components\Session\Session;
use SwagImportExport\Components\UploadPathProvider;
use SwagImportExport\Components\Utils\DataColumnOptions;
use SwagImportExport\Components\Utils\DataFilter;
use SwagImportExport\Components\Utils\DataLimit;
use SwagImportExport\CustomModels\Session as SessionEntity;

class DataFactory extends \Enlight_Class implements \Enlight_Hook
{
    private EntityRepository $sessionRepository;

    private CategoriesDbAdapter $categoriesDbAdapter;

    private ArticlesDbAdapter $articlesDbAdapter;

    private ArticlesInStockDbAdapter $articlesInStockDbAdapter;

    private ArticlesTranslationsDbAdapter $articlesTranslationsDbAdapter;

    private ArticlesPricesDbAdapter $articlesPricesDbAdapter;

    private ArticlesImagesDbAdapter $articlesImagesDbAdapter;

    private OrdersDbAdapter $ordersDbAdapter;

    private MainOrdersDbAdapter $mainOrdersDbAdapter;

    private CustomerDbAdapter $customerDbAdapter;

    private CustomerCompleteDbAdapter $customerCompleteDbAdapter;

    private NewsletterDbAdapter $newsletterDbAdapter;

    private TranslationsDbAdapter $translationsDbAdapter;

    private AddressDbAdapter $addressDbAdapter;

    private CategoryTranslationDbAdapter $categoryTranslationDbAdapter;

    private CategoriesDataManager $categoriesDataManager;

    private ArticleDataManager $articleDataManager;

    private CustomerDataManager $customerDataManager;

    private NewsletterDataManager $newsletterDataManager;

    private \Enlight_Event_EventManager $eventManager;

    private EntityManagerInterface $entityManager;

    private UploadPathProvider $uploadPathProvider;

    public function __construct(
        \Enlight_Event_EventManager $eventManager,
        EntityManagerInterface $entityManager,
        CategoriesDbAdapter $categoriesDbAdapter,
        ArticlesDbAdapter $articlesDbAdapter,
        ArticlesInStockDbAdapter $articlesInStockDbAdapter,
        ArticlesTranslationsDbAdapter $articlesTranslationsDbAdapter,
        ArticlesPricesDbAdapter $articlesPricesDbAdapter,
        ArticlesImagesDbAdapter $articlesImagesDbAdapter,
        OrdersDbAdapter $ordersDbAdapter,
        MainOrdersDbAdapter $mainOrdersDbAdapter,
        CustomerDbAdapter $customerDbAdapter,
        CustomerCompleteDbAdapter $customerCompleteDbAdapter,
        NewsletterDbAdapter $newsletterDbAdapter,
        TranslationsDbAdapter $translationsDbAdapter,
        AddressDbAdapter $addressDbAdapter,
        CategoryTranslationDbAdapter $categoryTranslationDbAdapter,
        CategoriesDataManager $categoriesDataManager,
        ArticleDataManager $articleDataManager,
        CustomerDataManager $customerDataManager,
        NewsletterDataManager $newsletterDataManager,
        UploadPathProvider $uploadPathProvider
    ) {
        $this->categoriesDbAdapter = $categoriesDbAdapter;
        $this->articlesDbAdapter = $articlesDbAdapter;
        $this->articlesInStockDbAdapter = $articlesInStockDbAdapter;
        $this->articlesTranslationsDbAdapter = $articlesTranslationsDbAdapter;
        $this->articlesPricesDbAdapter = $articlesPricesDbAdapter;
        $this->articlesImagesDbAdapter = $articlesImagesDbAdapter;
        $this->ordersDbAdapter = $ordersDbAdapter;
        $this->mainOrdersDbAdapter = $mainOrdersDbAdapter;
        $this->customerDbAdapter = $customerDbAdapter;
        $this->customerCompleteDbAdapter = $customerCompleteDbAdapter;
        $this->newsletterDbAdapter = $newsletterDbAdapter;
        $this->translationsDbAdapter = $translationsDbAdapter;
        $this->addressDbAdapter = $addressDbAdapter;
        $this->categoryTranslationDbAdapter = $categoryTranslationDbAdapter;
        $this->categoriesDataManager = $categoriesDataManager;
        $this->articleDataManager = $articleDataManager;
        $this->customerDataManager = $customerDataManager;
        $this->newsletterDataManager = $newsletterDataManager;
        $this->eventManager = $eventManager;
        $this->entityManager = $entityManager;
        $this->uploadPathProvider = $uploadPathProvider;
        $this->sessionRepository = $this->entityManager->getRepository(SessionEntity::class);
    }

    /**
     * @param Session $dataSession
     *
     * @return DataIO
     */
    public function createDataIO(DataDbAdapter $dbAdapter, $dataSession, Logger $logger)
    {
        $uploadPathProvider = $this->uploadPathProvider;

        return new DataIO($dbAdapter, $dataSession, $logger, $uploadPathProvider);
    }

    /**
     * Returns the necessary adapter
     *
     * @param string $adapterType
     */
    public function createDbAdapter($adapterType): DataDbAdapter
    {
        $event = $this->fireCreateFactoryEvent($adapterType);
        if ($event && $event instanceof \Enlight_Event_EventArgs
            && $event->getReturn() instanceof DataDbAdapter
        ) {
            return $event->getReturn();
        }

        switch ($adapterType) {
            case DataDbAdapter::CATEGORIES_ADAPTER:
                return $this->categoriesDbAdapter;
            case DataDbAdapter::ARTICLE_ADAPTER:
                return $this->articlesDbAdapter;
            case DataDbAdapter::ARTICLE_INSTOCK_ADAPTER:
                return $this->articlesInStockDbAdapter;
            case DataDbAdapter::ARTICLE_TRANSLATION_ADAPTER:
                return $this->articlesTranslationsDbAdapter;
            case DataDbAdapter::ARTICLE_PRICE_ADAPTER:
                return $this->articlesPricesDbAdapter;
            case DataDbAdapter::ARTICLE_IMAGE_ADAPTER:
                return $this->articlesImagesDbAdapter;
            case DataDbAdapter::ORDER_ADAPTER:
                return $this->ordersDbAdapter;
            case DataDbAdapter::MAIN_ORDER_ADAPTER:
                return $this->mainOrdersDbAdapter;
            case DataDbAdapter::CUSTOMER_ADAPTER:
                return $this->customerDbAdapter;
            case DataDbAdapter::CUSTOMER_COMPLETE_ADAPTER:
                return $this->customerCompleteDbAdapter;
            case DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER:
                return $this->newsletterDbAdapter;
            case DataDbAdapter::TRANSLATION_ADAPTER:
                return $this->translationsDbAdapter;
            case DataDbAdapter::ADDRESS_ADAPTER:
                return $this->addressDbAdapter;
            case DataDbAdapter::CATEGORIES_TRANSLATION_ADAPTER:
                return $this->categoryTranslationDbAdapter;
            default:
                throw new \Exception('Db adapter type is not valid');
        }
    }

    /**
     * Return necessary data manager
     *
     * @param string $managerType
     *
     * @return object dbAdapter
     */
    public function createDataManager($managerType)
    {
        switch ($managerType) {
            case DataDbAdapter::CATEGORIES_ADAPTER:
                return $this->categoriesDataManager;
            case DataDbAdapter::ARTICLE_ADAPTER:
                return $this->articleDataManager;
            case DataDbAdapter::CUSTOMER_ADAPTER:
                return $this->customerDataManager;
            case DataDbAdapter::NEWSLETTER_RECIPIENTS_ADAPTER:
                return $this->newsletterDataManager;
        }
    }

    /**
     * @param array $data
     *
     * @return Session
     */
    public function loadSession($data)
    {
        $sessionId = $data['sessionId'];

        $sessionEntity = $this->sessionRepository->findOneBy(['id' => $sessionId]);

        if (!$sessionEntity) {
            $sessionEntity = new SessionEntity();
        }

        return $this->createSession($sessionEntity);
    }

    /**
     * Returns columnOptions adapter
     *
     * @return \SwagImportExport\Components\Utils\DataColumnOptions
     */
    public function createColOpts($options)
    {
        return new DataColumnOptions($options);
    }

    /**
     * Returns limit adapter
     *
     * @return \SwagImportExport\Components\Utils\DataLimit
     */
    public function createLimit(array $limit)
    {
        return new DataLimit($limit);
    }

    /**
     * @param array<string, mixed> $filter
     *
     * @return \SwagImportExport\Components\Utils\DataFilter
     */
    public function createFilter($filter)
    {
        return new DataFilter($filter);
    }

    /**
     * @param string $adapterType
     *
     * @return \Enlight_Event_EventArgs|null
     */
    protected function fireCreateFactoryEvent($adapterType)
    {
        return $this->eventManager->notifyUntil(
            'Shopware_Components_SwagImportExport_Factories_CreateDbAdapter',
            ['subject' => $this, 'adapterType' => $adapterType]
        );
    }

    protected function createSession(SessionEntity $sessionEntity): Session
    {
        return new Session($sessionEntity);
    }
}
